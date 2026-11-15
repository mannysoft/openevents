<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'title' => 'General admission',
            'description' => null,
            'price' => 2500,
            'quantity_available' => 100,
            'quantity_sold' => 0,
            'min_per_order' => 1,
            'max_per_order' => 10,
            'is_hidden' => false,
            'sort_order' => 0,
        ];
    }
}
