<?php

namespace Tests\Feature;

use App\Mail\MissedPaymentAlert;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\PendingCommand;
use Tests\Concerns\BootsWithoutDatabase;
use Tests\Concerns\UsesEventPaymentTables;

/**
 * events:check-missed-payments: a buyer who pays and never comes back to the site has no
 * order. The command finds her payment in Stripe and tells tech once.
 */
class MissedPaymentBackstopTest extends TestCase
{
    use BootsWithoutDatabase;
    use UsesEventPaymentTables;

    private const NOW = '2026-10-27 20:00:00';

    private const TECH = 'tech@globalwomanclub.com';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse(self::NOW, 'UTC'));
        $this->setUpEventPaymentTables();
        Mail::fake();

        DB::table('events')->insert(['id' => 4237, 'title' => 'Mastermind & Networking', 'club' => 'paris', 'start_date' => '2026-10-29 08:30:00']);
    }

    protected function tearDown(): void
    {
        $this->tearDownEventPaymentTables();
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function minutesAgo(int $minutes): int
    {
        return Carbon::now()->getTimestamp() - $minutes * 60;
    }

    /**
     * A succeeded events-site PaymentIntent, shaped as Stripe returns it with latest_charge expanded.
     */
    private function intent(string $id, array $overrides = [], array $chargeOverrides = []): array
    {
        $created = $overrides['created'] ?? $this->minutesAgo(60);

        $charge = array_replace_recursive([
            'id' => 'ch_'.substr($id, 3),
            'object' => 'charge',
            'created' => $created + 60,
            'refunded' => false,
            'metadata' => [],
            'billing_details' => ['email' => null, 'name' => null],
            'receipt_email' => null,
        ], $chargeOverrides);

        return array_replace([
            'id' => $id,
            'object' => 'payment_intent',
            'status' => 'succeeded',
            'amount' => 3000,
            'amount_received' => 3000,
            'currency' => 'eur',
            'created' => $created,
            'receipt_email' => null,
            'metadata' => [
                'event_id' => '4237',
                'event_title' => 'Mastermind & Networking',
                'event_date' => '2026-10-29 08:30:00',
                'email' => 'buyer.one@example.test',
            ],
            'latest_charge' => $charge,
        ], $overrides);
    }

    private function sentAlerts(): array
    {
        return Mail::sent(MissedPaymentAlert::class)->all();
    }

    private function runCheck(): PendingCommand
    {
        return $this->artisan('events:check-missed-payments');
    }

    public function test_a_paid_booking_with_no_order_is_reported_to_tech(): void
    {
        $this->addClub('paris', 'acct_paris');
        $this->stripeHttp->intents['acct_paris'] = [$this->intent('pi_orphan_paris')];

        $this->runCheck()
            ->expectsOutputToContain('NEW pi_orphan_paris event 4237 on acct_paris')
            ->assertExitCode(0);

        $alerts = $this->sentAlerts();
        $this->assertCount(1, $alerts);
        $alert = $alerts[0];
        $this->assertTrue($alert->hasTo(self::TECH));
        $this->assertCount(1, $alert->to, 'tech only: no director address is resolved');
        $this->assertSame('pi_orphan_paris', $alert->missed[0]['payment_intent']);

        $this->assertSame('Action needed: 1 paid event booking with no order', $alert->envelope()->subject);
        $alert->assertSeeInText('1 paid event booking on globalwomanevents.com has no order.');
        $alert->assertSeeInText('Mastermind & Networking (event 4237)');
        $alert->assertSeeInText('2026-10-29 08:30:00');
        $alert->assertSeeInText('Club:           paris');
        $alert->assertSeeInText('30.00 EUR');
        $alert->assertSeeInText('Tue 27 Oct 2026, 19:01 UTC (19:01 UK time)');
        $alert->assertSeeInText('buyer.one@example.test');
        $alert->assertSeeInText('pi_orphan_paris');
        $alert->assertSeeInText('ch_orphan_paris');
        $alert->assertSeeInText('add this buyer to the event manually; do not ask her to rebook');

        // The list call asks for every page, expanded, inside the window.
        $list = $this->stripeHttp->requestsTo('get', '#^/v1/payment_intents$#');
        $this->assertSame(['data.latest_charge'], $list[0]['params']['expand']);
        $this->assertSame(Carbon::now()->subHours(48)->getTimestamp(), $list[0]['params']['created']['gte']);
        $this->assertSame(Carbon::now()->subMinutes(15)->getTimestamp(), $list[0]['params']['created']['lte']);
    }

    public function test_the_platform_account_is_checked_and_the_club_comes_from_the_event(): void
    {
        $this->stripeHttp->intents['platform'] = [$this->intent('pi_orphan_platform')];

        $this->runCheck()->assertExitCode(0);

        $alert = $this->sentAlerts()[0];
        $this->assertSame('platform', $alert->missed[0]['account']);
        $this->assertSame(['paris'], $alert->missed[0]['clubs']);
        $alert->assertSeeInText('Stripe account: platform account');
    }

    public function test_every_page_is_read(): void
    {
        $this->stripeHttp->pageSize = 2;
        DB::table('orders')->insert([
            ['event_id' => 4237, 'email' => 'a@example.test', 'order_reference' => 'A1', 'transaction_id' => 'ch_paid_1'],
            ['event_id' => 4237, 'email' => 'b@example.test', 'order_reference' => 'B1', 'transaction_id' => 'ch_paid_2'],
        ]);
        $this->stripeHttp->intents['platform'] = [
            $this->intent('pi_paid_1'),
            $this->intent('pi_paid_2'),
            $this->intent('pi_orphan_page_two'),
        ];

        $this->runCheck()->assertExitCode(0);

        $this->assertCount(2, $this->stripeHttp->requestsTo('get', '#^/v1/payment_intents$#'));
        $alert = $this->sentAlerts()[0];
        $this->assertSame(['pi_orphan_page_two'], array_column($alert->missed, 'payment_intent'));
    }

    public function test_a_payment_whose_charge_has_an_order_is_ignored(): void
    {
        DB::table('orders')->insert(['event_id' => 4237, 'email' => 'buyer.one@example.test', 'order_reference' => 'ORD1', 'transaction_id' => 'ch_returned']);
        $this->stripeHttp->intents['platform'] = [$this->intent('pi_returned')];

        $this->runCheck()
            ->expectsOutputToContain('With no order: 0 (0 not reported before).')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_a_payment_younger_than_the_grace_period_is_ignored(): void
    {
        $this->stripeHttp->intents['platform'] = [
            // Created 5 minutes ago: outside the list window.
            $this->intent('pi_created_recently', ['created' => $this->minutesAgo(5)]),
            // Page opened 40 minutes ago, paid 5 minutes ago: she may still be coming back.
            $this->intent('pi_paid_recently', ['created' => $this->minutesAgo(40)], ['created' => $this->minutesAgo(5)]),
        ];

        $this->runCheck()
            ->expectsOutputToContain('too recent: 1')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_payments_that_are_not_events_site_bookings_are_not_matched_to_orders(): void
    {
        // The two Lounge bookings below are recorded in the Lounge, never in orders.
        $this->addLoungePurchase('4237', 'app.member@example.test');
        $this->addLoungePurchase('4237', 'web.member@example.test');

        $this->stripeHttp->intents['platform'] = [
            // A membership or marketplace payment: no event.
            $this->intent('pi_no_event', ['metadata' => ['item' => 'Membership']]),
            // Not succeeded.
            $this->intent('pi_requires_action', ['status' => 'requires_action']),
            // A Lounge app booking from an app version that took the card itself.
            $this->intent('pi_lounge_app', ['metadata' => ['event_id' => '4237', 'event_title' => 'X', 'ticket_type' => 'In-Person', 'user_id' => '9', 'email' => 'app.member@example.test', 'type' => 'event_ticket']]),
            // A Lounge web checkout booking, even one that also carried an event_date.
            $this->intent('pi_lounge_web', ['metadata' => ['event_id' => '4237', 'event_date' => '2026-10-29', 'from_app' => 'yes', 'ticket_type' => 'Online', 'club_id' => '3']], ['billing_details' => ['email' => 'web.member@example.test']]),
            // Already refunded in full.
            $this->intent('pi_refunded', [], ['refunded' => true]),
        ];

        $this->runCheck()
            ->expectsOutputToContain('Lounge app/web: 2, with a Lounge purchase record: 2; other: 1; too recent: 0; refunded: 1')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_a_payment_is_reported_once(): void
    {
        $this->stripeHttp->intents['platform'] = [$this->intent('pi_orphan_once')];

        $this->runCheck()->assertExitCode(0);
        $this->runCheck()
            ->expectsOutputToContain('With no order: 1 (0 not reported before).')
            ->assertExitCode(0);

        $this->assertCount(1, $this->sentAlerts());

        // A new orphan later is still reported, on its own.
        $this->stripeHttp->intents['platform'][] = $this->intent('pi_orphan_later');
        $this->runCheck()->assertExitCode(0);

        $alerts = $this->sentAlerts();
        $this->assertCount(2, $alerts);
        $this->assertSame(['pi_orphan_later'], array_column($alerts[1]->missed, 'payment_intent'));
    }

    public function test_a_dry_run_sends_and_remembers_nothing(): void
    {
        $this->stripeHttp->intents['platform'] = [$this->intent('pi_orphan_dry')];

        $this->artisan('events:check-missed-payments', ['--dry-run' => true])
            ->expectsOutputToContain('Dry run: no email sent, nothing remembered.')
            ->assertExitCode(0);
        Mail::assertNothingSent();

        $this->runCheck()->assertExitCode(0);
        $this->assertCount(1, $this->sentAlerts());
    }

    public function test_an_account_that_refuses_access_does_not_stop_the_others_and_is_reported(): void
    {
        Log::spy();
        $this->addClub('newyork', 'acct_newyork');
        $this->addClub('paris', 'acct_paris');
        $this->stripeHttp->failures['acct_newyork'] = [403, 'invalid_request_error', 'The provided key does not have access to account acct_newyork'];
        $this->stripeHttp->intents['acct_paris'] = [$this->intent('pi_orphan_paris')];

        $this->runCheck()
            ->expectsOutputToContain('Checked 2 account(s); 1 could not be checked.')
            ->expectsOutputToContain('COULD NOT CHECK acct_newyork (newyork): PermissionException')
            ->assertExitCode(0);

        $alert = $this->sentAlerts()[0];
        $this->assertSame(['pi_orphan_paris'], array_column($alert->missed, 'payment_intent'));
        $this->assertSame('acct_newyork', $alert->failures[0]['account']);
        $alert->assertSeeInText('These Stripe accounts could not be checked');
        $alert->assertSeeInText('acct_newyork (club: newyork): PermissionException');

        Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context) => str_contains($message, 'could not check a Stripe account')
            && $context['account'] === 'acct_newyork' && $context['error'] === 'PermissionException')->once();
        Log::shouldHaveReceived('error')->withArgs(fn ($message, $context) => $message === 'Paid event booking with no order'
            && $context === ['payment_intent' => 'pi_orphan_paris', 'account' => 'acct_paris', 'event_id' => '4237'])->once();
    }

    public function test_an_account_that_cannot_be_checked_is_reported_daily_even_with_no_missed_payment(): void
    {
        $this->addClub('newyork', 'acct_newyork');
        $this->stripeHttp->failures['acct_newyork'] = [403, 'invalid_request_error', 'No access'];

        $this->runCheck()->assertExitCode(0);
        $this->runCheck()->assertExitCode(0);

        $alerts = $this->sentAlerts();
        $this->assertCount(1, $alerts, 'not every ten minutes');
        $this->assertSame([], $alerts[0]->missed);
        $this->assertSame('Missed-payment check: 1 Stripe account could not be checked', $alerts[0]->envelope()->subject);

        Carbon::setTestNow(Carbon::now()->addDay()->addMinute());
        $this->runCheck()->assertExitCode(0);
        $this->assertCount(2, $this->sentAlerts(), 'reported again a day later');
    }

    public function test_an_earlier_order_for_the_same_email_is_pointed_out(): void
    {
        DB::table('orders')->insert(['event_id' => 4237, 'email' => 'Buyer.One@example.test', 'order_reference' => 'FIRSTORDER', 'transaction_id' => 'ch_first']);
        $this->stripeHttp->intents['platform'] = [$this->intent('pi_second_payment')];

        $this->runCheck()->assertExitCode(0);

        $alert = $this->sentAlerts()[0];
        $this->assertSame('FIRSTORDER', $alert->missed[0]['existing_order']);
        $alert->assertSeeInText('already has order FIRSTORDER for this event, so this may be a second payment');
    }

    public function test_a_returned_buyer_email_on_the_charge_is_used_when_the_intent_has_none(): void
    {
        $this->stripeHttp->intents['platform'] = [$this->intent(
            'pi_old_style',
            ['metadata' => ['event_id' => '4237', 'event_title' => 'Mastermind & Networking', 'event_date' => 'N/A']],
            ['billing_details' => ['email' => 'card.holder@example.test', 'name' => 'Card Holder']]
        )];

        $this->runCheck()->assertExitCode(0);

        $alert = $this->sentAlerts()[0];
        $this->assertSame('card.holder@example.test', $alert->missed[0]['email']);
        // event_date N/A falls back to the event's own start date.
        $this->assertSame('2026-10-29 08:30:00', $alert->missed[0]['event_date']);
        $alert->assertSeeInText('Buyer name:     Card Holder');
    }

    public function test_a_failed_send_is_not_remembered(): void
    {
        $this->stripeHttp->intents['platform'] = [$this->intent('pi_orphan_retry')];
        config(['missed_payments.alert_to' => 'not-an-address']);

        $this->runCheck()->assertExitCode(1);
        Mail::assertNothingSent();

        config(['missed_payments.alert_to' => 'tech@globalwomanclub.com, ops@example.test']);
        $this->runCheck()->assertExitCode(0);

        $alert = $this->sentAlerts()[0];
        $this->assertTrue($alert->hasTo('ops@example.test'));
        $this->assertSame(['pi_orphan_retry'], array_column($alert->missed, 'payment_intent'));
    }

    public function test_it_runs_every_ten_minutes(): void
    {
        $events = array_values(array_filter(
            $this->app->make(Schedule::class)->events(),
            fn ($event) => str_contains((string) $event->command, 'events:check-missed-payments')
        ));

        $this->assertCount(1, $events);
        $this->assertSame('*/10 * * * *', $events[0]->expression);
        $this->assertTrue($events[0]->withoutOverlapping);
    }
}
