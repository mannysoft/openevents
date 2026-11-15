<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\Organiser;
use App\Mail\OrderConfirmation;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function orderWithTicket(): Order
    {
        $organiser = Organiser::factory()->stripeReady()->create();
        $event = Event::factory()->live()->create(['organiser_id' => $organiser->id]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'quantity_sold' => 0]);

        $order = Order::factory()->create([
            'event_id' => $event->id,
            'organiser_id' => $organiser->id,
            'status' => OrderStatus::Reserved,
            'total' => 2500,
            'currency_code' => 'GBP',
            'paid_at' => null,
        ]);

        $order->items()->create([
            'ticket_id' => $ticket->id,
            'description' => $ticket->title,
            'unit_price' => 2500,
            'quantity' => 1,
            'total' => 2500,
        ]);

        return $order->refresh();
    }

    private function payload(string $type, array $object): array
    {
        return [
            'id' => 'evt_test_'.uniqid(),
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => $object],
        ];
    }

    public function test_payment_succeeded_completes_the_order_and_is_idempotent(): void
    {
        config(['services.stripe.webhook_secret' => null]);

        $order = $this->orderWithTicket();
        $ticket = $order->items->first()->ticket;

        $payload = $this->payload('payment_intent.succeeded', [
            'id' => 'pi_webhook_1',
            'object' => 'payment_intent',
            'metadata' => ['order' => $order->reference],
        ]);

        $this->postJson('/stripe/webhook', $payload)->assertOk();
        $this->postJson('/stripe/webhook', $payload)->assertOk();

        $order->refresh();

        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(1, $ticket->refresh()->quantity_sold);
    }

    public function test_payment_failure_cancels_a_reserved_order(): void
    {
        config(['services.stripe.webhook_secret' => null]);

        $order = $this->orderWithTicket();

        $this->postJson('/stripe/webhook', $this->payload('payment_intent.payment_failed', [
            'id' => 'pi_webhook_2',
            'object' => 'payment_intent',
            'metadata' => ['order' => $order->reference],
        ]))->assertOk();

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
    }

    public function test_charge_refunded_syncs_the_refunded_total(): void
    {
        config(['services.stripe.webhook_secret' => null]);

        $order = $this->orderWithTicket();
        $order->forceFill([
            'status' => OrderStatus::Completed,
            'stripe_payment_intent_id' => 'pi_webhook_3',
            'paid_at' => now(),
        ])->save();

        $this->postJson('/stripe/webhook', $this->payload('charge.refunded', [
            'id' => 'ch_1',
            'object' => 'charge',
            'payment_intent' => 'pi_webhook_3',
            'amount_refunded' => 1000,
        ]))->assertOk();

        $order->refresh();
        $this->assertSame(1000, $order->refunded_amount);
        $this->assertSame(OrderStatus::Completed, $order->status);

        $this->postJson('/stripe/webhook', $this->payload('charge.refunded', [
            'id' => 'ch_1',
            'object' => 'charge',
            'payment_intent' => 'pi_webhook_3',
            'amount_refunded' => 1000,
        ]))->assertOk();

        $this->assertSame(1000, $order->refresh()->refunded_amount);
    }

    public function test_account_updated_syncs_connect_flags(): void
    {
        config(['services.stripe.webhook_secret' => null]);

        $organiser = Organiser::factory()->create(['stripe_account_id' => 'acct_hook']);

        $this->postJson('/stripe/webhook', $this->payload('account.updated', [
            'id' => 'acct_hook',
            'object' => 'account',
            'charges_enabled' => true,
            'details_submitted' => true,
        ]))->assertOk();

        $this->assertTrue($organiser->refresh()->stripeReady());
    }

    public function test_unhandled_types_are_acknowledged(): void
    {
        config(['services.stripe.webhook_secret' => null]);

        $this->postJson('/stripe/webhook', $this->payload('customer.created', ['id' => 'cus_1']))
            ->assertOk();
    }

    public function test_completed_payments_queue_the_confirmation_email_once(): void
    {
        config(['services.stripe.webhook_secret' => null]);
        Mail::fake();

        $order = $this->orderWithTicket();

        $payload = $this->payload('payment_intent.succeeded', [
            'id' => 'pi_webhook_mail',
            'object' => 'payment_intent',
            'metadata' => ['order' => $order->reference],
        ]);

        $this->postJson('/stripe/webhook', $payload)->assertOk();
        $this->postJson('/stripe/webhook', $payload)->assertOk();

        Mail::assertQueued(OrderConfirmation::class, 1);
    }
}
