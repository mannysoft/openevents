<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\DiscountCode;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscountCode>
 */
class DiscountCodeFactory extends Factory
{
    protected $model = DiscountCode::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'code' => fake()->unique()->bothify('SAVE##??'),
            'type' => DiscountType::Percentage,
            'value' => 10,
            'quantity_available' => null,
            'quantity_used' => 0,
            'min_quantity' => 1,
            'is_active' => true,
        ];
    }
}
