<?php

namespace Database\Factories;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendee>
 */
class AttendeeFactory extends Factory
{
    protected $model = Attendee::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'event_id' => fn (array $attributes) => Order::find($attributes['order_id'])?->event_id ?? Event::factory(),
            'ticket_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'reference' => 'TKT-'.fake()->unique()->bothify('????####'),
        ];
    }

    public function forTicket(Ticket $ticket): static
    {
        return $this->state(fn () => [
            'ticket_id' => $ticket->id,
            'event_id' => $ticket->event_id,
        ]);
    }
}
