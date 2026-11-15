<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organiser;
use App\Models\User;
use App\Services\Stripe\ConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeConnectTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_express_account_and_stores_the_id(): void
    {
        $organiser = Organiser::factory()->create();
        $stripe = $this->fakeStripe();

        $accountId = app(ConnectService::class)->createAccount($organiser);

        $this->assertSame('acct_fake1', $accountId);
        $this->assertSame('express', $stripe->requests[0]['params']['type']);
        $this->assertSame($organiser->id, (int) $stripe->requests[0]['params']['metadata']['organiser_id']);
        $this->assertSame($accountId, $organiser->refresh()->stripe_account_id);
    }

    public function test_account_creation_is_idempotent(): void
    {
        $organiser = Organiser::factory()->create();
        $stripe = $this->fakeStripe();
        $service = app(ConnectService::class);

        $first = $service->createAccount($organiser);
        $second = $service->createAccount($organiser->refresh());

        $this->assertSame($first, $second);
        $this->assertCount(1, $stripe->accounts);
    }

    public function test_onboarding_url_and_status_sync(): void
    {
        $organiser = Organiser::factory()->create(['stripe_account_id' => 'acct_existing']);
        $stripe = $this->fakeStripe();
        $stripe->chargesEnabled = false;
        $stripe->detailsSubmitted = false;

        $url = app(ConnectService::class)->onboardingUrl($organiser);

        $this->assertSame('https://connect.stripe.test/acct_existing', $url);

        $service = app(ConnectService::class);
        $service->refreshStatus($organiser);
        $this->assertFalse($organiser->refresh()->stripeReady());

        $stripe->chargesEnabled = true;
        $stripe->detailsSubmitted = true;
        $service->refreshStatus($organiser);

        $organiser->refresh();
        $this->assertTrue($organiser->stripeReady());
        $this->assertNotNull($organiser->stripe_onboarded_at);
    }

    public function test_events_cannot_be_published_until_stripe_is_ready(): void
    {
        $user = User::factory()->create();
        $organiser = Organiser::factory()->create(['user_id' => $user->id]);
        $event = Event::factory()->create(['organiser_id' => $organiser->id]);

        $this->assertFalse($user->can('publish', $event));

        $organiser->forceFill([
            'stripe_account_id' => 'acct_ready',
            'stripe_charges_enabled' => true,
            'stripe_details_submitted' => true,
        ])->save();

        $this->assertTrue($user->can('publish', $event->refresh()));
    }
}
