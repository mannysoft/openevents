<?php

namespace Tests\Feature;

use App\Livewire\Admin\Organisers\Form;
use App\Models\Event;
use App\Models\Organiser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrganiserOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_organiser_redirects_to_stripe_onboarding(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Livewire::test(Form::class)
            ->set('name', 'Acme Events')
            ->set('email', 'hello@acme.test')
            ->call('save')
            ->assertHasNoErrors();

        $organiser = Organiser::query()->sole();

        $component->assertRedirect(route('admin.organisers.stripe', $organiser));

        $this->assertSame($user->id, $organiser->user_id);
        $this->assertSame('Acme Events', $organiser->name);
    }

    public function test_editing_an_organiser_redirects_to_its_events(): void
    {
        $user = User::factory()->create();
        $organiser = Organiser::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        Livewire::test(Form::class, ['organiser' => $organiser])
            ->set('name', 'Renamed Events')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.events.index', $organiser));

        $this->assertSame('Renamed Events', $organiser->fresh()->name);
    }

    public function test_the_organisers_list_prompts_for_stripe_until_the_organiser_is_ready(): void
    {
        $user = User::factory()->create();
        Organiser::factory()->create(['user_id' => $user->id, 'name' => 'Pending Events']);

        $this->actingAs($user)
            ->get(route('admin.organisers.index'))
            ->assertOk()
            ->assertSee('Stripe not connected')
            ->assertSee('Connect Stripe')
            ->assertDontSee('Stripe settings');
    }

    public function test_the_organisers_list_links_to_settings_once_stripe_is_ready(): void
    {
        $user = User::factory()->create();
        Organiser::factory()->stripeReady()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('admin.organisers.index'))
            ->assertOk()
            ->assertSee('Stripe connected')
            ->assertSee('Stripe settings')
            ->assertDontSee('Connect Stripe');
    }

    public function test_the_events_screen_warns_until_stripe_is_ready(): void
    {
        $user = User::factory()->create();
        $organiser = Organiser::factory()->create(['user_id' => $user->id]);
        Event::factory()->create(['organiser_id' => $organiser->id]);

        $this->actingAs($user)
            ->get(route('admin.events.index', $organiser))
            ->assertOk()
            ->assertSee('Stripe is not connected yet')
            ->assertSee('Connect Stripe');
    }

    public function test_the_events_screen_does_not_warn_when_stripe_is_ready(): void
    {
        $user = User::factory()->create();
        $organiser = Organiser::factory()->stripeReady()->create(['user_id' => $user->id]);
        Event::factory()->create(['organiser_id' => $organiser->id]);

        $this->actingAs($user)
            ->get(route('admin.events.index', $organiser))
            ->assertOk()
            ->assertDontSee('Stripe is not connected yet');
    }

    public function test_other_users_cannot_reach_stripe_onboarding(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $organiser = Organiser::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->get(route('admin.organisers.stripe', $organiser))
            ->assertForbidden();
    }
}
