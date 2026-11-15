<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\OrderStatus;
use App\Livewire\Public\Checkout;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Order;
use App\Models\Organiser;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    private Organiser $organiser;

    private Event $event;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organiser = Organiser::factory()->stripeReady()->create([
            'user_id' => User::factory(),
            'name' => 'Acme Events',
        ]);
        $this->event = Event::factory()->live()->create([
            'organiser_id' => $this->organiser->id,
            'title' => 'Summer Conference',
        ]);
        $this->ticket = Ticket::factory()->create([
            'event_id' => $this->event->id,
            'title' => 'General admission',
            'price' => 2500,
            'quantity_available' => 10,
        ]);
    }

    public function test_events_index_lists_live_events_only(): void
    {
        $draft = Event::factory()->create([
            'organiser_id' => $this->organiser->id,
            'title' => 'Secret Draft Event',
            'status' => EventStatus::Draft,
        ]);

        $this->get('/events')
            ->assertOk()
            ->assertSee('Summer Conference')
            ->assertDontSee('Secret Draft Event');
    }

    public function test_event_page_shows_tickets_and_organiser(): void
    {
        $this->get(route('events.show', $this->event))
            ->assertOk()
            ->assertSee('Summer Conference')
            ->assertSee('General admission')
            ->assertSee('£25.00')
            ->assertSee('Acme Events');
    }

    public function test_draft_events_are_not_public(): void
    {
        $this->event->update(['status' => EventStatus::Draft]);

        $this->get(route('events.show', $this->event->refresh()))->assertNotFound();
    }

    public function test_organiser_page_lists_their_events(): void
    {
        $this->get(route('organisers.show', $this->organiser))
            ->assertOk()
            ->assertSee('Acme Events')
            ->assertSee('Summer Conference');
    }

    public function test_checkout_creates_a_reservation_and_payment_intent(): void
    {
        $stripe = $this->fakeStripe();

        Livewire::test(Checkout::class, ['event' => $this->event])
            ->call('increment', $this->ticket->id)
            ->call('increment', $this->ticket->id)
            ->assertSet('quantities', [$this->ticket->id => 2])
            ->call('continueToDetails')
            ->assertSet('step', 2)
            ->assertCount('attendees', 2)
            ->set('firstName', 'Ada')
            ->set('lastName', 'Lovelace')
            ->set('email', 'ada@example.com')
            ->set('attendees.0.first_name', 'Ada')
            ->set('attendees.0.last_name', 'Lovelace')
            ->set('attendees.0.email', 'ada@example.com')
            ->set('attendees.1.first_name', 'Grace')
            ->set('attendees.1.last_name', 'Hopper')
            ->set('attendees.1.email', 'grace@example.com')
            ->call('pay')
            ->assertHasNoErrors()
            ->assertSet('step', 3)
            ->assertSet('total', 5000)
            ->assertSet('clientSecret', 'pi_fake_secret');

        $order = Order::first();

        $this->assertNotNull($order);
        $this->assertSame(OrderStatus::Reserved, $order->status);
        $this->assertSame(5000, $order->total);
        $this->assertSame(250, $order->application_fee_amount);
        $this->assertCount(2, $order->attendees);
        $this->assertNotNull($stripe->intents);
    }

    public function test_checkout_validates_the_required_attendee_details(): void
    {
        $this->fakeStripe();

        Livewire::test(Checkout::class, ['event' => $this->event])
            ->call('increment', $this->ticket->id)
            ->call('continueToDetails')
            ->set('firstName', 'Ada')
            ->set('lastName', 'Lovelace')
            ->set('email', 'ada@example.com')
            ->set('attendees.0.first_name', '')
            ->call('pay')
            ->assertHasErrors('attendees.0.first_name');

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_applies_a_discount_code(): void
    {
        $this->fakeStripe();

        \App\Models\DiscountCode::factory()->create([
            'event_id' => $this->event->id,
            'code' => 'HALF',
            'value' => 50,
        ]);

        Livewire::test(Checkout::class, ['event' => $this->event])
            ->call('increment', $this->ticket->id)
            ->call('continueToDetails')
            ->set('firstName', 'Ada')
            ->set('lastName', 'Lovelace')
            ->set('email', 'ada@example.com')
            ->set('attendees.0.first_name', 'Ada')
            ->set('attendees.0.last_name', 'Lovelace')
            ->set('attendees.0.email', 'ada@example.com')
            ->set('discountCode', 'half')
            ->call('pay')
            ->assertHasNoErrors()
            ->assertSet('total', 1250);
    }

    public function test_free_orders_are_completed_without_stripe(): void
    {
        $this->ticket->update(['price' => 0]);

        Livewire::test(Checkout::class, ['event' => $this->event])
            ->call('increment', $this->ticket->id)
            ->call('continueToDetails')
            ->set('firstName', 'Ada')
            ->set('lastName', 'Lovelace')
            ->set('email', 'ada@example.com')
            ->set('attendees.0.first_name', 'Ada')
            ->set('attendees.0.last_name', 'Lovelace')
            ->set('attendees.0.email', 'ada@example.com')
            ->call('pay')
            ->assertRedirect(route('orders.confirmation', Order::first()->reference));

        $this->assertSame(OrderStatus::Completed, Order::first()->status);
    }

    public function test_checkout_is_blocked_when_the_organiser_cannot_take_payments(): void
    {
        $this->organiser->update(['stripe_charges_enabled' => false]);

        Livewire::test(Checkout::class, ['event' => $this->event])
            ->call('increment', $this->ticket->id)
            ->call('continueToDetails')
            ->assertHasErrors('quantities')
            ->assertSet('step', 1);
    }

    public function test_confirmation_and_ticket_pages_render_for_paid_orders(): void
    {
        $order = Order::factory()->create([
            'event_id' => $this->event->id,
            'organiser_id' => $this->organiser->id,
            'status' => OrderStatus::Completed,
            'reference' => 'ORD-PUBLIC1',
            'total' => 2500,
        ]);

        $attendee = Attendee::factory()->forTicket($this->ticket)->create([
            'order_id' => $order->id,
            'reference' => 'TKT-PUBLIC1',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
        ]);

        $this->get(route('orders.confirmation', $order))
            ->assertOk()
            ->assertSee('ORD-PUBLIC1')
            ->assertSee('TKT-PUBLIC1');

        $this->get(route('tickets.show', $attendee))
            ->assertOk()
            ->assertSee('TKT-PUBLIC1')
            ->assertSee('<svg', false);

        $this->get(route('tickets.pdf', $attendee))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_tickets_are_not_visible_for_unpaid_orders(): void
    {
        $order = Order::factory()->create([
            'event_id' => $this->event->id,
            'organiser_id' => $this->organiser->id,
            'status' => OrderStatus::Reserved,
        ]);

        $attendee = Attendee::factory()->forTicket($this->ticket)->create([
            'order_id' => $order->id,
            'reference' => 'TKT-UNPAID1',
        ]);

        $this->get(route('tickets.show', $attendee))->assertNotFound();
    }
}
