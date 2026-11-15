<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\Organiser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'reference' => 'ORD-'.fake()->unique()->bothify('??####'),
            'event_id' => Event::factory(),
            'organiser_id' => fn (array $attributes) => Event::find($attributes['event_id'])?->organiser_id ?? Organiser::factory(),
            'status' => OrderStatus::Completed,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'subtotal' => 2500,
            'total' => 2500,
            'currency_code' => 'GBP',
            'paid_at' => now(),
        ];
    }
}
