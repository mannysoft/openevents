<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Answer;
use App\Models\Attendee;
use App\Models\DiscountCode;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * @param  array{first_name: ?string, last_name: ?string, email: string}  $customer
     * @param  array<int, int>  $lines  ticket id => quantity
     * @param  array<int, array<string, mixed>>  $attendees
     *
     * @throws ValidationException
     */
    public function createReservation(
        Event $event,
        array $customer,
        array $lines,
        array $attendees = [],
        ?string $discountCode = null,
    ): Order {
        if (! $event->isLive()) {
            throw ValidationException::withMessages([
                'tickets' => 'This event is not currently on sale.',
            ]);
        }

        $lines = array_filter(array_map('intval', $lines));

        if ($lines === []) {
            throw ValidationException::withMessages([
                'tickets' => 'Select at least one ticket.',
            ]);
        }

        $tickets = $event->tickets()->whereIn('id', array_keys($lines))->get()->keyBy('id');
        $items = [];
        $subtotal = 0;

        foreach ($lines as $ticketId => $quantity) {
            $ticket = $tickets->get($ticketId);

            if (! $ticket instanceof Ticket) {
                throw ValidationException::withMessages([
                    'tickets' => 'A selected ticket is no longer available.',
                ]);
            }

            if (! $ticket->isOnSale()) {
                throw ValidationException::withMessages([
                    'tickets' => "\"{$ticket->title}\" is not on sale.",
                ]);
            }

            if ($quantity < $ticket->min_per_order || $quantity > $ticket->max_per_order) {
                throw ValidationException::withMessages([
                    'tickets' => "\"{$ticket->title}\" must be between {$ticket->min_per_order} and {$ticket->max_per_order} tickets.",
                ]);
            }

            $remaining = $ticket->remaining();

            if ($remaining !== null && $quantity > $remaining) {
                throw ValidationException::withMessages([
                    'tickets' => "Only {$remaining} of \"{$ticket->title}\" remaining.",
                ]);
            }

            $lineTotal = $ticket->price * $quantity;
            $subtotal += $lineTotal;

            $items[] = [
                'ticket_id' => $ticket->id,
                'description' => $ticket->title,
                'unit_price' => $ticket->price,
                'quantity' => $quantity,
                'total' => $lineTotal,
            ];
        }

        $discount = null;
        $discountTotal = 0;

        if ($discountCode !== null && trim($discountCode) !== '') {
            $discount = $event->discountCodes()
                ->where('code', mb_strtoupper(trim($discountCode)))
                ->first();

            if (! $discount instanceof DiscountCode || ! $discount->isUsable()) {
                throw ValidationException::withMessages([
                    'discount_code' => 'That discount code is not valid.',
                ]);
            }

            if (array_sum($lines) < $discount->min_quantity) {
                throw ValidationException::withMessages([
                    'discount_code' => "This code requires at least {$discount->min_quantity} tickets.",
                ]);
            }

            $discountTotal = $discount->discountFor($subtotal);
        }

        return DB::transaction(function () use ($event, $customer, $lines, $attendees, $tickets, $items, $subtotal, $discount, $discountTotal) {
            $order = Order::create([
                'reference' => ReferenceGenerator::order(),
                'event_id' => $event->id,
                'organiser_id' => $event->organiser_id,
                'status' => OrderStatus::Reserved,
                'first_name' => $customer['first_name'] ?? null,
                'last_name' => $customer['last_name'] ?? null,
                'email' => $customer['email'],
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'total' => max(0, $subtotal - $discountTotal),
                'currency_code' => $event->currency_code,
                'discount_code_id' => $discount?->id,
            ]);

            foreach ($items as $item) {
                $order->items()->create($item);
            }

            $this->createAttendees($order, $tickets, $lines, $attendees, $customer);

            return $order->fresh(['items', 'attendees']);
        });
    }

    public function complete(Order $order, ?string $paymentIntentId = null): Order
    {
        return DB::transaction(function () use ($order, $paymentIntentId) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($locked->status === OrderStatus::Completed) {
                return $locked;
            }

            if ($locked->status !== OrderStatus::Reserved) {
                throw ValidationException::withMessages([
                    'order' => 'This order can no longer be paid.',
                ]);
            }

            $locked->forceFill([
                'status' => OrderStatus::Completed,
                'paid_at' => now(),
                'stripe_payment_intent_id' => $paymentIntentId ?? $locked->stripe_payment_intent_id,
            ])->save();

            foreach ($locked->items as $item) {
                if ($item->ticket_id !== null) {
                    Ticket::query()->whereKey($item->ticket_id)->increment('quantity_sold', $item->quantity);
                }
            }

            if ($locked->discount_code_id !== null) {
                DiscountCode::query()->whereKey($locked->discount_code_id)->increment('quantity_used');
            }

            return $locked;
        });
    }

    public function cancel(Order $order): void
    {
        DB::transaction(function () use ($order) {
            if ($order->status === OrderStatus::Reserved) {
                $order->attendees()->forceDelete();

                $order->forceFill([
                    'status' => OrderStatus::Cancelled,
                    'cancelled_at' => now(),
                ])->save();
            }
        });
    }

    public function markRefunded(Order $order, int $amountMinor): Order
    {
        $refunded = min($order->total, $order->refunded_amount + $amountMinor);

        return $this->applyRefundedTotal($order, $refunded);
    }

    /**
     * Sets the refunded total to the absolute amount reported by Stripe so
     * webhook retries never double count.
     */
    public function syncRefunded(Order $order, int $absoluteAmountMinor): Order
    {
        return $this->applyRefundedTotal($order, min($order->total, $absoluteAmountMinor));
    }

    private function applyRefundedTotal(Order $order, int $refunded): Order
    {
        if ($refunded < $order->refunded_amount) {
            return $order;
        }

        $fullyRefunded = $refunded >= $order->total;

        $order->forceFill([
            'refunded_amount' => $refunded,
            'status' => $fullyRefunded ? OrderStatus::Refunded : $order->status,
            'refunded_at' => $fullyRefunded ? ($order->refunded_at ?? now()) : $order->refunded_at,
        ])->save();

        if ($fullyRefunded) {
            $order->attendees()->update(['is_refunded' => true]);
        }

        return $order;
    }

    public function cancelAttendee(Attendee $attendee): void    {
        $attendee->forceFill(['is_cancelled' => true])->save();
    }

    public function restoreAttendee(Attendee $attendee): void
    {
        $attendee->forceFill(['is_cancelled' => false])->save();
    }

    public function checkIn(Attendee $attendee, ?User $user = null): bool
    {
        $attendee->loadMissing('order');

        if ($attendee->is_cancelled || $attendee->is_refunded) {
            return false;
        }

        if (! $attendee->order?->isCompleted()) {
            return false;
        }

        if ($attendee->isCheckedIn()) {
            return false;
        }

        $attendee->forceFill([
            'checked_in_at' => now(),
            'checked_in_by' => $user?->id,
        ])->save();

        return true;
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @param  array<int, int>  $lines
     * @param  array<int, array<string, mixed>>  $attendees
     * @param  array<string, mixed>  $customer
     */
    private function createAttendees(Order $order, Collection $tickets, array $lines, array $attendees, array $customer): void
    {
        if (count($attendees) === 0) {
            $attendees = [];

            foreach ($lines as $ticketId => $quantity) {
                for ($i = 0; $i < $quantity; $i++) {
                    $attendees[] = [
                        'ticket_id' => $ticketId,
                        'first_name' => $customer['first_name'] ?? null,
                        'last_name' => $customer['last_name'] ?? null,
                        'email' => $customer['email'],
                    ];
                }
            }
        }

        foreach ($attendees as $data) {
            $attendee = Attendee::create([
                'order_id' => $order->id,
                'event_id' => $order->event_id,
                'ticket_id' => $data['ticket_id'] ?? null,
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'] ?? null,
                'reference' => ReferenceGenerator::attendee(),
            ]);

            foreach (($data['answers'] ?? []) as $questionId => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                Answer::create([
                    'attendee_id' => $attendee->id,
                    'question_id' => $questionId,
                    'answer_text' => is_array($value) ? implode(', ', $value) : (string) $value,
                ]);
            }
        }
    }
}
