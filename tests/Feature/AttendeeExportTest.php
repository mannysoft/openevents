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

class AttendeeExportTest extends TestCase
{
    use RefreshDatabase;

    private function booking(User $user): array
    {
        $organiser = Organiser::factory()->create(['user_id' => $user->id]);
        $event = Event::factory()->live()->create(['organiser_id' => $organiser->id]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'title' => 'VIP']);
        $order = Order::factory()->create([
            'event_id' => $event->id,
            'organiser_id' => $organiser->id,
            'status' => OrderStatus::Completed,
            'reference' => 'ORD-EXPORT01',
        ]);

        Attendee::factory()->forTicket($ticket)->create([
            'order_id' => $order->id,
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'email' => 'grace@example.com',
            'reference' => 'TKT-EXPORT01',
        ]);

        return [$event, $ticket, $order];
    }

    public function test_organisers_can_export_attendees_as_csv(): void
    {
        $user = User::factory()->create();
        [$event] = $this->booking($user);

        $response = $this->actingAs($user)->get(route('admin.attendees.export', [$event, 'csv']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('TKT-EXPORT01', $csv);
        $this->assertStringContainsString('Grace Hopper', $csv);
        $this->assertStringContainsString('VIP', $csv);
    }

    public function test_other_organisers_cannot_export(): void
    {
        [$event] = $this->booking(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get(route('admin.attendees.export', [$event, 'csv']))
            ->assertForbidden();
    }

    public function test_guests_cannot_export(): void
    {
        [$event] = $this->booking(User::factory()->create());

        $this->get(route('admin.attendees.export', [$event, 'csv']))
            ->assertRedirect(route('login'));
    }
}
