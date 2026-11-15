<?php

namespace App\Http\Controllers;

use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\Organiser;
use App\Services\OrderService;
use App\Services\Stripe\ConnectService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Stripe\Event as StripeEvent;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(
        Request $request,
        OrderService $orders,
        ConnectService $connect,
    ): Response {
        $event = $this->parseEvent($request);

        if ($event === null) {
            return response('Invalid payload or signature', 400);
        }

        match ($event->type) {
            'payment_intent.succeeded' => $this->paymentSucceeded($event, $orders),
            'payment_intent.payment_failed', 'payment_intent.canceled' => $this->paymentFailed($event, $orders),
            'charge.refunded' => $this->chargeRefunded($event, $orders),
            'account.updated' => $this->accountUpdated($event, $connect),
            default => null,
        };

        return response('ok');
    }

    private function parseEvent(Request $request): ?StripeEvent
    {
        $secret = config('services.stripe.webhook_secret');

        if (blank($secret)) {
            return rescue(fn () => StripeEvent::constructFrom(
                json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR)
            ), null, false);
        }

        try {
            return Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $secret,
            );
        } catch (SignatureVerificationException|\UnexpectedValueException) {
            return null;
        }
    }

    private function paymentSucceeded(StripeEvent $event, OrderService $orders): void
    {
        $object = $event->data->object;

        $order = $this->findOrder($object->metadata->order ?? null, $object->id);

        if (! $order) {
            return;
        }

        $wasCompleted = $order->isCompleted();
        $completed = $orders->complete($order, $object->id);

        if (! $wasCompleted) {
            Mail::to($completed->email)->queue(
                new OrderConfirmation($completed->load(['event.organiser', 'attendees.ticket']))
            );
        }
    }

    private function paymentFailed(StripeEvent $event, OrderService $orders): void
    {
        $object = $event->data->object;

        $order = $this->findOrder($object->metadata->order ?? null, $object->id);

        if ($order) {
            $orders->cancel($order);
        }
    }

    private function chargeRefunded(StripeEvent $event, OrderService $orders): void
    {
        $charge = $event->data->object;

        $order = Order::query()
            ->where('stripe_payment_intent_id', $charge->payment_intent)
            ->first();

        if ($order) {
            $orders->syncRefunded($order, (int) $charge->amount_refunded);
        }
    }

    private function accountUpdated(StripeEvent $event, ConnectService $connect): void
    {
        $organiser = Organiser::query()
            ->where('stripe_account_id', $event->data->object->id)
            ->first();

        if (! $organiser) {
            return;
        }

        try {
            // The event only signals a change; Accounts v2 is the source of truth.
            $connect->refreshStatus($organiser);
        } catch (ApiErrorException $e) {
            report($e);
        }
    }

    private function findOrder(?string $reference, ?string $paymentIntentId): ?Order
    {
        return Order::query()
            ->when($reference, fn ($query) => $query->where('reference', $reference))
            ->when(! $reference && $paymentIntentId, fn ($query) => $query->where('stripe_payment_intent_id', $paymentIntentId))
            ->first();
    }
}
