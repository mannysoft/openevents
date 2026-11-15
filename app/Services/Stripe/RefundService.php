<?php

namespace App\Services\Stripe;

use App\Models\Order;
use Illuminate\Validation\ValidationException;
use Stripe\Refund;
use Stripe\StripeClient;

class RefundService
{
    public function __construct(private readonly StripeClient $stripe) {}

    /**
     * Refunds a payment intent and reverses the destination transfer. Stripe
     * refunds the application fee proportionally when requested.
     *
     * @throws ValidationException
     */
    public function refund(Order $order, ?int $amountMinor = null): Refund
    {
        if (! $order->isRefundable()) {
            throw ValidationException::withMessages([
                'refund' => 'This order cannot be refunded.',
            ]);
        }

        $amount = $amountMinor === null
            ? $order->refundableMinor()
            : min($amountMinor, $order->refundableMinor());

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'refund' => 'The refund amount must be greater than zero.',
            ]);
        }

        $refund = $this->stripe->refunds->create([
            'payment_intent' => $order->stripe_payment_intent_id,
            'amount' => $amount,
            'refund_application_fee' => $order->application_fee_amount > 0,
            'reverse_transfer' => true,
        ], [
            'idempotency_key' => sprintf(
                'refund-%s-%d-%d',
                $order->reference,
                $order->refunded_amount,
                $amount,
            ),
        ]);

        return $refund;
    }
}
