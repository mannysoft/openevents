<?php

namespace Tests\Feature;

use App\Livewire\Admin\Organisers\StripeConnect;
use App\Models\Event;
use App\Models\Organiser;
use App\Models\User;
use App\Services\Stripe\ConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StripeConnectTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_v2_account_and_stores_the_id(): void
    {
        $organiser = Organiser::factory()->create();
        $stripe = $this->fakeStripe();

        $accountId = app(ConnectService::class)->createAccount($organiser);

        $this->assertSame('acct_fake1', $accountId);
        $this->assertSame('/v2/core/accounts', $stripe->requests[0]['path']);

        $params = $stripe->requests[0]['params'];

        $this->assertSame('express', $params['dashboard']);
        $this->assertSame('gb', $params['identity']['country']);
        $this->assertSame($organiser->name, $params['display_name']);
        $this->assertSame($organiser->email, $params['contact_email']);
        $this->assertSame('application', $params['defaults']['responsibilities']['fees_collector']);
        $this->assertSame('application', $params['defaults']['responsibilities']['losses_collector']);
        $this->assertTrue($params['configuration']['merchant']['capabilities']['card_payments']['requested']);
        $this->assertTrue($params['configuration']['recipient']['capabilities']['stripe_balance']['stripe_transfers']['requested']);
        $this->assertContains('configuration.merchant', $params['include']);
        $this->assertContains('requirements', $params['include']);
        $this->assertSame((string) $organiser->id, $params['metadata']['organiser_id']);
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

    public function test_onboarding_url_uses_v2_account_links(): void
    {
        $organiser = Organiser::factory()->create(['stripe_account_id' => 'acct_existing']);
        $stripe = $this->fakeStripe();

        $url = app(ConnectService::class)->onboardingUrl($organiser);

        $this->assertSame('https://connect.stripe.test/acct_existing', $url);

        $request = collect($stripe->requests)->firstWhere('path', '/v2/core/account_links');

        $this->assertNotNull($request);
        $this->assertSame('acct_existing', $request['params']['account']);
        $this->assertSame('account_onboarding', $request['params']['use_case']['type']);
        $this->assertSame(
            route('stripe.connect.refresh', $organiser),
            $request['params']['use_case']['account_onboarding']['refresh_url'],
        );
        $this->assertSame(
            route('stripe.connect.return', $organiser),
            $request['params']['use_case']['account_onboarding']['return_url'],
        );
    }

    public function test_status_sync_reads_v2_capabilities_and_requirements(): void
    {
        $organiser = Organiser::factory()->create(['stripe_account_id' => 'acct_existing']);
        $stripe = $this->fakeStripe();
        $stripe->cardPaymentsStatus = 'pending';
        $stripe->requirementEntries = [
            [
                'awaiting_action_from' => 'user',
                'description' => 'representative.address.postal_code',
                'minimum_deadline' => ['status' => 'currently_due'],
            ],
        ];

        $service = app(ConnectService::class);

        $service->refreshStatus($organiser);
        $organiser->refresh();

        $this->assertFalse($organiser->stripeReady());
        $this->assertFalse($organiser->stripe_details_submitted);
        $this->assertNull($organiser->stripe_onboarded_at);

        $stripe->cardPaymentsStatus = 'active';
        $stripe->requirementEntries = [];

        $service->refreshStatus($organiser);
        $organiser->refresh();

        $this->assertTrue($organiser->stripeReady());
        $this->assertTrue($organiser->stripe_details_submitted);
        $this->assertNotNull($organiser->stripe_onboarded_at);

        $retrieve = collect($stripe->requests)->last();

        $this->assertSame('/v2/core/accounts/acct_existing', $retrieve['path']);
        $this->assertContains('configuration.merchant', $retrieve['params']['include']);
        $this->assertContains('requirements', $retrieve['params']['include']);
    }

    public function test_dashboard_url_uses_the_v1_login_link_endpoint_for_a_v2_account(): void
    {
        $organiser = Organiser::factory()->create(['stripe_account_id' => 'acct_existing']);
        $stripe = $this->fakeStripe();

        $url = app(ConnectService::class)->dashboardUrl($organiser);

        $this->assertSame('https://dashboard.stripe.test/acct_existing', $url);
        $this->assertSame('/v1/accounts/acct_existing/login_links', $stripe->requests[0]['path']);
    }

    public function test_missing_requirements_payload_does_not_clear_an_onboarded_organiser(): void
    {
        $organiser = Organiser::factory()->stripeReady()->create(['stripe_account_id' => 'acct_existing']);
        $stripe = $this->fakeStripe();
        $stripe->omitRequirements = true;

        app(ConnectService::class)->refreshStatus($organiser);

        $organiser->refresh();

        $this->assertTrue($organiser->stripe_details_submitted);
        $this->assertTrue($organiser->stripeReady());
        $this->assertNotNull($organiser->stripe_onboarded_at);
    }

    public function test_requirements_map_v2_entries_by_deadline(): void
    {
        $organiser = Organiser::factory()->create(['stripe_account_id' => 'acct_existing']);
        $stripe = $this->fakeStripe();
        $stripe->cardPaymentsStatus = 'restricted';
        $stripe->cardPaymentsStatusDetails = [['code' => 'requirements.past_due', 'resolution' => 'provide_info']];
        $stripe->requirementEntries = [
            [
                'awaiting_action_from' => 'user',
                'description' => 'representative.address.postal_code',
                'minimum_deadline' => ['status' => 'currently_due'],
            ],
            [
                'awaiting_action_from' => 'user',
                'description' => 'business_details.address.line1',
                'minimum_deadline' => ['status' => 'past_due'],
                'errors' => [['code' => 'invalid_street_address', 'description' => 'Invalid address.']],
            ],
            [
                'awaiting_action_from' => 'stripe',
                'description' => 'owners.0.date_of_birth',
                'minimum_deadline' => ['status' => 'eventually_due'],
            ],
        ];

        $requirements = app(ConnectService::class)->requirements($organiser);

        $this->assertSame([
            'representative.address.postal_code',
            'business_details.address.line1',
        ], array_column($requirements['currently_due'], 'description'));
        $this->assertSame('user', $requirements['currently_due'][0]['awaiting_action_from']);
        $this->assertSame(['invalid_street_address'], $requirements['currently_due'][1]['errors']);
        $this->assertSame(['owners.0.date_of_birth'], array_column($requirements['eventually_due'], 'description'));
        $this->assertSame('requirements.past_due', $requirements['disabled_reason']);
    }

    public function test_the_admin_panel_lists_v2_requirements(): void
    {
        $user = User::factory()->create();
        $organiser = Organiser::factory()->create([
            'user_id' => $user->id,
            'stripe_account_id' => 'acct_existing',
        ]);

        $stripe = $this->fakeStripe();
        $stripe->cardPaymentsStatus = 'pending';
        $stripe->requirementEntries = [
            [
                'awaiting_action_from' => 'user',
                'description' => 'representative.address.postal_code',
                'minimum_deadline' => ['status' => 'currently_due'],
                'errors' => [['code' => 'invalid_street_address', 'description' => 'Invalid address.']],
            ],
            [
                'awaiting_action_from' => 'stripe',
                'description' => 'owners.0.date_of_birth',
                'minimum_deadline' => ['status' => 'eventually_due'],
            ],
        ];

        $this->actingAs($user);

        Livewire::test(StripeConnect::class, ['organiser' => $organiser])
            ->assertSee('Required now')
            ->assertSee('Representative Address Postal Code')
            ->assertSee('invalid_street_address')
            ->assertSee('Required later')
            ->assertSee('Owners Date Of Birth')
            ->assertSee('Stripe is reviewing this');
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
