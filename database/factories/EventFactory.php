<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organiser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 week', '+2 months');

        return [
            'organiser_id' => Organiser::factory(),
            'title' => fake()->sentence(4),
            'slug' => null,
            'description' => fake()->paragraph(),
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+3 hours'),
            'timezone' => 'Europe/London',
            'venue_name' => fake()->company(),
            'address1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'country_code' => 'GB',
            'currency_code' => 'GBP',
            'status' => EventStatus::Draft,
            'is_public' => true,
        ];
    }

    public function live(): static
    {
        return $this->state(fn () => ['status' => EventStatus::Live]);
    }
}
