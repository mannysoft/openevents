<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Livewire\Admin\Events\Form;
use App\Models\Event;
use App\Models\Organiser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_publish_without_a_connected_account_using_the_platform_account(): void
    {
        $user = User::factory()->create();
        $organiser = Organiser::factory()->create(['user_id' => $user->id]);
        $event = Event::factory()->create(['organiser_id' => $organiser->id]);

        $this->actingAs($user);

        Livewire::test(Form::class, ['organiser' => $organiser, 'event' => $event])
            ->call('publish')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.events.edit', [$organiser, $event]));

        $this->assertSame(EventStatus::Live, $event->fresh()->status);
        $this->assertStringContainsString('platform Stripe account', (string) session('status'));
    }

    public function test_events_publish_with_the_connected_account_once_stripe_is_ready(): void
    {
        $user = User::factory()->create();
        $organiser = Organiser::factory()->stripeReady()->create(['user_id' => $user->id]);
        $event = Event::factory()->create(['organiser_id' => $organiser->id]);

        $this->actingAs($user);

        Livewire::test(Form::class, ['organiser' => $organiser, 'event' => $event])
            ->call('publish')
            ->assertHasNoErrors();

        $this->assertSame(EventStatus::Live, $event->fresh()->status);
        $this->assertSame('Event published.', session('status'));
    }

    public function test_other_users_cannot_publish_an_event(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $organiser = Organiser::factory()->create(['user_id' => $owner->id]);
        $event = Event::factory()->create(['organiser_id' => $organiser->id]);

        $this->assertTrue($owner->can('publish', $event));
        $this->assertFalse($intruder->can('publish', $event));

        $this->actingAs($intruder)
            ->get(route('admin.events.edit', [$organiser, $event]))
            ->assertForbidden();
    }
}
