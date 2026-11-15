<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\Organiser;
use App\Services\OrderService;
use App\Services\Stripe\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RefundServiceTest extends TestCase
{
    use RefreshDatabase;

    private function paidOrder(int $total = 10000, int $fee = 500): Order
    {
        $organiser = Organiser::factory()->stripeReady()->create();
        $event = Event::factory()->live()->create(['organiser_id' => $organiser->id]);

        return Order::factory()->create([
            'event_id' => $event->id,
            'organiser_id' => $organiser->id,
            'status' => OrderStatus::Completed,
            'total' => $total,
            'currency_code' => 'GBP',
            'stripe_payment_intent_id' => 'pi_paid_1',
            'stripe_account_id' => $organiser->stripe_account_id,
            'application_fee_amount' => $fee,
            'paid_at' => now(),
        ]);
    }

    public function test_it_refunds_the_full_amount_and_reverses_the_transfer(): void
    {
        $order = $this->paidOrder();
        $stripe = $this->fakeStripe();

        app(RefundService::class)->refund($order);

        $request = collect($stripe->requests)->firstWhere('path', '/v1/refunds');

        $this->assertSame('pi_paid_1', $request['params']['payment_intent']);
        $this->assertSame(10000, $request['params']['amount']);
        $this->assertTrue(filter_var($request['params']['refund_application_fee'], FILTER_VALIDATE_BOOLEAN));
        $this->assertTrue(filter_var($request['params']['reverse_transfer'], FILTER_VALIDATE_BOOLEAN));
    }

    public function test_platform_account_charges_are_refunded_without_reversing_a_transfer(): void
    {
        $organiser = Organiser::factory()->create();
        $event = Event::factory()->live()->create(['organiser_id' => $organiser->id]);
        $order = Order::factory()->create([
            'event_id' => $event->id,
            'organiser_id' => $organiser->id,
            'status' => OrderStatus::Completed,
            'total' => 10000,
            'currency_code' => 'GBP',
            'stripe_payment_intent_id' => 'pi_platform_1',
            'stripe_account_id' => null,
            'application_fee_amount' => 500,
            'paid_at' => now(),
        ]);

        $stripe = $this->fakeStripe();

        app(RefundService::class)->refund($order);

        $request = collect($stripe->requests)->firstWhere('path', '/v1/refunds');

        $this->assertSame('pi_platform_1', $request['params']['payment_intent']);
        $this->assertSame(10000, $request['params']['amount']);
        $this->assertArrayNotHasKey('reverse_transfer', $request['params']);
        $this->assertArrayNotHasKey('refund_application_fee', $request['params']);
    }

    public function test_partial_refunds_are_clamped_to_the_refundable_amount(): void
    {
        $order = $this->paidOrder();
        $order->update(['refunded_amount' => 8000]);
        $stripe = $this->fakeStripe();

        app(RefundService::class)->refund($order, 5000);

        $request = collect($stripe->requests)->firstWhere('path', '/v1/refunds');

        $this->assertSame(2000, $request['params']['amount']);
    }

    public function test_fully_refunded_orders_cannot_be_refunded_again(): void
    {
        $order = $this->paidOrder();
        app(OrderService::class)->syncRefunded($order, 10000);

        $this->fakeStripe();

        $this->expectException(ValidationException::class);

        app(RefundService::class)->refund($order->refresh());
    }

    public function test_sync_refunded_marks_orders_and_attendees(): void
    {
        $order = $this->paidOrder();
        $order->attendees()->create([
            'event_id' => $order->event_id,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'reference' => 'TKT-REFUND1',
        ]);

        app(OrderService::class)->syncRefunded($order, 10000);

        $order->refresh();

        $this->assertSame(OrderStatus::Refunded, $order->status);
        $this->assertSame(10000, $order->refunded_amount);
        $this->assertNotNull($order->refunded_at);
        $this->assertTrue($order->attendees->first()->refresh()->is_refunded);
    }
}
