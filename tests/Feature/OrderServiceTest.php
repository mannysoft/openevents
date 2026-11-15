<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\DiscountCode;
use App\Models\Event;
use App\Models\Organiser;
use App\Models\Ticket;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private function liveEventWithTicket(array $ticketOverrides = []): array
    {
        $organiser = Organiser::factory()->stripeReady()->create();
        $event = Event::factory()->live()->create(['organiser_id' => $organiser->id]);
        $ticket = Ticket::factory()->create(array_merge([
            'event_id' => $event->id,
            'price' => 2500,
            'quantity_available' => 10,
        ], $ticketOverrides));

        return [$event, $ticket];
    }

    public function test_it_reserves_an_order_with_attendees(): void
    {
        [$event, $ticket] = $this->liveEventWithTicket();

        $order = app(OrderService::class)->createReservation(
            $event,
            ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com'],
            [$ticket->id => 2],
        );

        $this->assertSame(OrderStatus::Reserved, $order->status);
        $this->assertSame(5000, $order->subtotal);
        $this->assertSame(5000, $order->total);
        $this->assertCount(1, $order->items);
        $this->assertCount(2, $order->attendees);
        $this->assertStringStartsWith('ORD-', $order->reference);
        $this->assertStringStartsWith('TKT-', $order->attendees->first()->reference);
    }

    public function test_it_rejects_tickets_above_the_per_order_limit(): void
    {
        [$event, $ticket] = $this->liveEventWithTicket(['max_per_order' => 2]);

        $this->expectException(ValidationException::class);

        app(OrderService::class)->createReservation(
            $event,
            ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com'],
            [$ticket->id => 3],
        );
    }

    public function test_it_rejects_sold_out_tickets(): void
    {
        [$event, $ticket] = $this->liveEventWithTicket(['quantity_available' => 1, 'quantity_sold' => 1]);

        $this->expectException(ValidationException::class);

        app(OrderService::class)->createReservation(
            $event,
            ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com'],
            [$ticket->id => 1],
        );
    }

    public function test_draft_events_cannot_be_booked(): void
    {
        [$event, $ticket] = $this->liveEventWithTicket();
        $event->update(['status' => \App\Enums\EventStatus::Draft]);

        $this->expectException(ValidationException::class);

        app(OrderService::class)->createReservation(
            $event->refresh(),
            ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com'],
            [$ticket->id => 1],
        );
    }

    public function test_discount_codes_reduce_the_total_and_are_counted_on_completion(): void
    {
        [$event, $ticket] = $this->liveEventWithTicket();

        $code = DiscountCode::factory()->create([
            'event_id' => $event->id,
            'code' => 'SAVE10',
            'value' => 10,
            'quantity_available' => 5,
        ]);

        $service = app(OrderService::class);

        $order = $service->createReservation(
            $event,
            ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com'],
            [$ticket->id => 2],
            [],
            'save10',
        );

        $this->assertSame(500, $order->discount_total);
        $this->assertSame(4500, $order->total);
        $this->assertSame($code->id, $order->discount_code_id);

        $service->complete($order);

        $this->assertSame(1, $code->refresh()->quantity_used);
        $this->assertSame(2, $ticket->refresh()->quantity_sold);
    }

    public function test_completing_is_idempotent(): void
    {
        [$event, $ticket] = $this->liveEventWithTicket();

        $service = app(OrderService::class);
        $order = $service->createReservation(
            $event,
            ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com'],
            [$ticket->id => 1],
        );

        $service->complete($order);
        $service->complete($order->refresh());

        $this->assertSame(1, $ticket->refresh()->quantity_sold);
        $this->assertSame(OrderStatus::Completed, $order->refresh()->status);
    }

    public function test_cancelling_a_reservation_removes_attendees(): void
    {
        [$event, $ticket] = $this->liveEventWithTicket();

        $service = app(OrderService::class);
        $order = $service->createReservation(
            $event,
            ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com'],
            [$ticket->id => 1],
        );

        $service->cancel($order);

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        $this->assertSame(0, \App\Models\Attendee::withTrashed()->count());
    }

    public function test_check_in_requires_a_completed_order(): void
    {
        [$event, $ticket] = $this->liveEventWithTicket();

        $service = app(OrderService::class);
        $order = $service->createReservation(
            $event,
            ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com'],
            [$ticket->id => 1],
        );

        $attendee = $order->attendees->first();

        $this->assertFalse($service->checkIn($attendee));

        $service->complete($order);

        $this->assertTrue($service->checkIn($attendee->refresh()));
        $this->assertTrue($attendee->refresh()->isCheckedIn());
        $this->assertFalse($service->checkIn($attendee->refresh()));
    }
}
