<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Organiser;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo Organiser',
            'email' => 'demo@example.com',
            'password' => Hash::make('password'),
        ]);

        $organiser = Organiser::factory()->create([
            'user_id' => $user->id,
            'name' => 'Demo Events Ltd',
        ]);

        $event = Event::factory()->live()->create([
            'organiser_id' => $organiser->id,
            'title' => 'Demo Conference 2026',
        ]);

        Ticket::factory()->create([
            'event_id' => $event->id,
            'title' => 'General admission',
            'price' => 2500,
            'quantity_available' => 100,
        ]);

        Ticket::factory()->create([
            'event_id' => $event->id,
            'title' => 'VIP',
            'price' => 7500,
            'quantity_available' => 20,
            'sort_order' => 1,
        ]);
    }
}
