<?php

namespace Database\Factories;

use App\Models\Organiser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organiser>
 */
class OrganiserFactory extends Factory
{
    protected $model = Organiser::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => null,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'about' => fake()->sentence(),
            'currency_code' => 'GBP',
            'timezone' => 'Europe/London',
        ];
    }

    public function stripeReady(): static
    {
        return $this->state(fn () => [
            'stripe_account_id' => 'acct_'.fake()->regexify('[A-Za-z0-9]{16}'),
            'stripe_charges_enabled' => true,
            'stripe_details_submitted' => true,
            'stripe_onboarded_at' => now(),
        ]);
    }
}
