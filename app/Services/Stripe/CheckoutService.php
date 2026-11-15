<?php

namespace App\Services\Stripe;

use App\Models\Order;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class CheckoutService
{
    public function __construct(private readonly StripeClient $stripe) {}

    public function applicationFee(Order $order): int
    {
        $fee = (int) round($order->total * $order->organiser->feePercent() / 100);

        return max(0, min($fee, $order->total));
    }

    /**
     * Charges are destination charges when the organiser has a connected account
     * that can take payments. Otherwise the platform account configured in .env
     * takes the payment and the organiser is settled outside Stripe.
     */
    public function createPaymentIntent(Order $order): PaymentIntent
    {
        $destination = $order->organiser->stripeReady()
            ? $order->organiser->stripe_account_id
            : null;

        $fee = $this->applicationFee($order);

        $payload = [
            'amount' => $order->total,
            'currency' => strtolower($order->currency_code),
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'order' => $order->reference,
                'event_id' => (string) $order->event_id,
                'organiser_id' => (string) $order->organiser_id,
            ],
            'receipt_email' => $order->email,
        ];

        if ($destination !== null) {
            $payload['application_fee_amount'] = $fee;
            $payload['transfer_data'] = ['destination' => $destination];
        }

        $intent = $this->stripe->paymentIntents->create($payload, [
            'idempotency_key' => 'order-'.$order->reference,
        ]);

        $order->forceFill([
            'stripe_payment_intent_id' => $intent->id,
            // Null when the platform account took the payment for an organiser
            // that has not completed Stripe onboarding.
            'stripe_account_id' => $destination,
            // Recorded for reporting: the platform retains this when settling.
            'application_fee_amount' => $fee,
        ])->save();

        return $intent;
    }
}
