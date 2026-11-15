<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Order;
use App\Models\Organiser;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organiser $organiser;

    private Event $event;

    private Order $order;

    private function fixtures(): void
    {
        $this->user = User::factory()->create();
        $this->organiser = Organiser::factory()->stripeReady()->create(['user_id' => $this->user->id]);
        $this->event = Event::factory()->live()->create(['organiser_id' => $this->organiser->id]);
        $ticket = Ticket::factory()->create([
            'event_id' => $this->event->id,
            'price' => 2500,
            'quantity_sold' => 1,
        ]);

        $this->order = Order::factory()->create([
            'event_id' => $this->event->id,
            'organiser_id' => $this->organiser->id,
            'status' => OrderStatus::Completed,
            'reference' => 'ORD-ADMIN01',
            'total' => 2500,
            'paid_at' => now(),
        ]);

        Attendee::factory()->forTicket($ticket)->create([
            'order_id' => $this->order->id,
            'reference' => 'TKT-ADMIN01',
        ]);
    }

    public function test_every_admin_page_renders_for_the_owner(): void
    {
        $this->fixtures();
        $this->actingAs($this->user);

        $routes = [
            route('admin.dashboard'),
            route('admin.organisers.index'),
            route('admin.organisers.create'),
            route('admin.organisers.edit', $this->organiser),
            route('admin.organisers.stripe', $this->organiser),
            route('admin.events.index', $this->organiser),
            route('admin.events.create', $this->organiser),
            route('admin.events.edit', [$this->organiser, $this->event]),
            route('admin.tickets.index', $this->event),
            route('admin.orders.index', $this->event),
            route('admin.orders.show', $this->order),
            route('admin.attendees.index', $this->event),
            route('admin.checkin.index', $this->event),
            route('admin.discounts.index', $this->event),
            route('admin.questions.index', $this->event),
            route('admin.messages.index', $this->event),
            route('admin.payouts.index'),
        ];

        foreach ($routes as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_other_organisers_cannot_reach_event_admin_pages(): void
    {
        $this->fixtures();
        $intruder = User::factory()->create();

        $this->actingAs($intruder);

        $this->get(route('admin.organisers.edit', $this->organiser))->assertForbidden();
        $this->get(route('admin.events.index', $this->organiser))->assertForbidden();
        $this->get(route('admin.events.edit', [$this->organiser, $this->event]))->assertForbidden();
        $this->get(route('admin.tickets.index', $this->event))->assertForbidden();
        $this->get(route('admin.orders.index', $this->event))->assertForbidden();
        $this->get(route('admin.attendees.index', $this->event))->assertForbidden();
        $this->get(route('admin.orders.show', $this->order))->assertForbidden();
    }

    public function test_missing_records_return_404(): void
    {
        $this->fixtures();
        $this->actingAs($this->user);

        $this->get('/dashboard/orders/ORD-DOES-NOT-EXIST')->assertNotFound();
        $this->get('/e/does-not-exist')->assertNotFound();
    }
}
