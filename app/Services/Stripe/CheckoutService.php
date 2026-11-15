<?php

namespace App\Services\Stripe;

use App\Models\Order;
use Illuminate\Validation\ValidationException;
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
     * @throws ValidationException
     */
    public function createPaymentIntent(Order $order): PaymentIntent
    {
        $organiser = $order->organiser;

        if (! $organiser->stripeReady()) {
            throw ValidationException::withMessages([
                'payment' => 'This organiser is not able to accept payments yet.',
            ]);
        }

        $fee = $this->applicationFee($order);

        $intent = $this->stripe->paymentIntents->create([
            'amount' => $order->total,
            'currency' => strtolower($order->currency_code),
            'automatic_payment_methods' => ['enabled' => true],
            'application_fee_amount' => $fee,
            'transfer_data' => [
                'destination' => $organiser->stripe_account_id,
            ],
            'metadata' => [
                'order' => $order->reference,
                'event_id' => (string) $order->event_id,
                'organiser_id' => (string) $order->organiser_id,
            ],
            'receipt_email' => $order->email,
        ], [
            'idempotency_key' => 'order-'.$order->reference,
        ]);

        $order->forceFill([
            'stripe_payment_intent_id' => $intent->id,
            'stripe_account_id' => $organiser->stripe_account_id,
            'application_fee_amount' => $fee,
        ])->save();

        return $intent;
    }
}
