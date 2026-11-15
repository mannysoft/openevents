<?php

namespace Tests\Feature;

use App\Mail\MissedPaymentAlert;
use App\Services\EventPaymentLookup;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\PendingCommand;
use Stripe\PaymentIntent;
use Tests\Concerns\BootsWithoutDatabase;
use Tests\Concerns\UsesEventPaymentTables;

/**
 * events:check-missed-payments, Lounge side: a Lounge app or web booking is charged in Stripe and
 * recorded only when the app or page calls the Lounge's POST /api/v1/tickets afterwards. When that
 * call never lands, the payment has no row in Wo_Clickfunnel_Event_Purchase. The command finds it
 * and tells tech once.
 */
class LoungeMissedBookingTest extends TestCase
{
    use BootsWithoutDatabase;
    use UsesEventPaymentTables;

    private const NOW = '2026-10-27 20:00:00';

    private const BUYER = 'member.paris@example.test';

    /**
     * The metadata of the Paris payment (event 4237, 27 Oct 2026, 18:26 UTC, Paris connected
     * account): exactly these eleven keys, as StripeProductTrait::createEventSession copies them
     * onto a direct charge. The values are made up.
     */
    private const PARIS_METADATA = [
        'amountInCents' => '3000',
        'applicationFee' => '1441',
        'club_id' => '61',
        'commissionBase' => '3000',
        'event_id' => '4237',
        'from_app' => 'yes',
        'is_bring_friend' => 'false',
        'stripe_fee' => '117',
        'taxAmount' => '0',
        'ticket_type' => 'In-Person',
        'totalAfterFee' => '2883',
    ];

    /** What the Flutter app's checkout returns to: createEventSession's default (the app sends none). */
    private const APP_SUCCESS_URL = 'https://backend.globalwomanlounge.com/payment/success?session_id={CHECKOUT_SESSION_ID}';

    /** What the React web app's EventDetailModal sends. */
    private const WEB_SUCCESS_URL = 'https://app.globalwomanlounge.com/events?success=true&event_id=4237&ticket_type=Online&amount=30&currency=eur&session_id={CHECKOUT_SESSION_ID}';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse(self::NOW, 'UTC'));
        $this->setUpEventPaymentTables();
        Mail::fake();

        DB::table('events')->insert([
            ['id' => 4237, 'title' => 'Mastermind & Networking', 'club' => 'paris', 'start_date' => '2026-10-29 08:30:00'],
            ['id' => 4238, 'title' => 'Another Paris Event', 'club' => 'paris', 'start_date' => '2026-11-05 08:30:00'],
            ['id' => 4240, 'title' => 'Kent Breakfast', 'club' => 'kent', 'start_date' => '2026-10-30 08:00:00'],
        ]);

        $this->addClub('paris', 'acct_paris');
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
     * A succeeded Lounge PaymentIntent with the Paris metadata, latest_charge expanded. Checkout
     * puts the email it collected on the card's billing details.
     */
    private function loungeIntent(string $id, array $metadata = [], array $chargeOverrides = [], array $overrides = []): array
    {
        $created = $overrides['created'] ?? $this->minutesAgo(60);

        $charge = array_replace_recursive([
            'id' => 'ch_'.substr($id, 3),
            'object' => 'charge',
            'created' => $created + 60,
            'refunded' => false,
            'metadata' => [],
            'billing_details' => ['email' => self::BUYER, 'name' => 'Marie Paris'],
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
            'metadata' => array_replace(self::PARIS_METADATA, $metadata),
            'latest_charge' => $charge,
        ], $overrides);
    }

    /**
     * The completed Checkout Session that took a payment.
     */
    private function checkoutSession(string $id, array $intent, string $successUrl, array $overrides = []): array
    {
        return array_replace([
            'id' => $id,
            'object' => 'checkout.session',
            'created' => $intent['created'] - 120,
            'status' => 'complete',
            'payment_status' => 'paid',
            'payment_intent' => $intent,
            'metadata' => $intent['metadata'],
            'success_url' => $successUrl,
            'customer_email' => null,
            'customer_details' => ['email' => self::BUYER, 'name' => 'Marie Paris'],
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

    /** Session lookups made on one account. */
    private function sessionRequests(string $account): array
    {
        return array_values(array_filter(
            $this->stripeHttp->requestsTo('get', '#^/v1/checkout/sessions$#'),
            fn (array $r) => $r['account'] === $account
        ));
    }

    public function test_the_paris_fixture_has_exactly_the_keys_of_the_paris_payment(): void
    {
        $keys = array_keys(self::PARIS_METADATA);
        sort($keys);

        $this->assertSame(
            ['amountInCents', 'applicationFee', 'club_id', 'commissionBase', 'event_id', 'from_app', 'is_bring_friend', 'stripe_fee', 'taxAmount', 'ticket_type', 'totalAfterFee'],
            $keys
        );
        $this->assertArrayNotHasKey('event_date', self::PARIS_METADATA);
        $this->assertArrayNotHasKey('email', self::PARIS_METADATA);

        $this->assertTrue(EventPaymentLookup::isLoungeEventMetadata(self::PARIS_METADATA));
        $this->assertFalse(EventPaymentLookup::isEventsSitePayment(PaymentIntent::constructFrom($this->loungeIntent('pi_shape'))));
    }

    public function test_an_app_payment_with_a_purchase_row_is_ignored(): void
    {
        // storeTicket wrote the row under her Lounge account email, in another case.
        $this->addLoungePurchase('4237', 'Member.Paris@Example.test');
        $this->stripeHttp->intents['acct_paris'] = [$this->loungeIntent('pi_paris_recorded')];

        $this->runCheck()
            ->expectsOutputToContain('Lounge app/web: 1, with a Lounge purchase record: 1;')
            ->expectsOutputToContain('With no order: 0 (0 not reported before).')
            ->assertExitCode(0);

        Mail::assertNothingSent();
        $this->assertSame([], $this->sessionRequests('acct_paris'), 'matched on the charge: no session lookup');
    }

    public function test_an_app_payment_with_no_purchase_row_is_reported_to_tech(): void
    {
        Log::spy();
        $intent = $this->loungeIntent('pi_paris_app');
        $this->stripeHttp->intents['acct_paris'] = [$intent];
        $this->stripeHttp->sessions['acct_paris'] = [$this->checkoutSession('cs_paris_app', $intent, self::APP_SUCCESS_URL)];

        $this->runCheck()
            ->expectsOutputToContain('NEW pi_paris_app event 4237 on acct_paris (Lounge app booking)')
            ->assertExitCode(0);

        $alerts = $this->sentAlerts();
        $this->assertCount(1, $alerts);
        $alert = $alerts[0];
        $this->assertTrue($alert->hasTo('tech@globalwomanclub.com'));
        $this->assertCount(1, $alert->to);
        $this->assertSame('pi_paris_app', $alert->missed[0]['payment_intent']);
        $this->assertSame(EventPaymentLookup::LOUNGE_APP, $alert->missed[0]['source']);

        $this->assertSame('Action needed: 1 paid Lounge booking with no ticket record', $alert->envelope()->subject);
        $alert->assertSeeInText('1 paid event booking made in the Global Woman Lounge app or website has no ticket record.');
        $alert->assertSeeInText('Booked in:      Lounge app booking');
        $alert->assertSeeInText('Mastermind & Networking (event 4237)');
        $alert->assertSeeInText('Event date:     2026-10-29 08:30:00');
        $alert->assertSeeInText('Club:           paris');
        $alert->assertSeeInText('Ticket:         In-Person');
        $alert->assertSeeInText('30.00 EUR');
        $alert->assertSeeInText('Tue 27 Oct 2026, 19:01 UTC (19:01 UK time)');
        $alert->assertSeeInText('Buyer email:    '.self::BUYER);
        $alert->assertSeeInText('Stripe account: acct_paris');
        $alert->assertSeeInText('Charge:         ch_paris_app');
        $alert->assertSeeInText('Checkout:       cs_paris_app');
        $alert->assertSeeInText('look for her on the event\'s ticket list in the Lounge /manager');
        $alert->assertSeeInText('do not ask her to pay again');
        $alert->assertSeeInText('refund this payment in Stripe instead');
        $alert->assertDontSeeInText('globalwomanevents.com has no order');
        $alert->assertDontSeeInText('Bring a friend');
        $alert->assertDontSeeInText('already has');

        // The session was looked up on her club's account, by this payment.
        $lookups = $this->sessionRequests('acct_paris');
        $this->assertCount(1, $lookups);
        $this->assertSame('pi_paris_app', $lookups[0]['params']['payment_intent']);

        Log::shouldHaveReceived('error')->withArgs(fn ($message, $context) => $message === 'Paid Lounge event booking with no purchase record'
            && $context === ['payment_intent' => 'pi_paris_app', 'account' => 'acct_paris', 'event_id' => '4237', 'source' => 'app'])->once();
    }

    public function test_free_guest_deleted_other_event_and_earlier_rows_do_not_record_the_payment(): void
    {
        // A director's guest invite (events /api/saveFreeUser): not a purchase.
        $this->addLoungePurchase('4237', self::BUYER, ['amount' => 0, 'is_purchased' => 0, 'created' => '2026-10-27 05:00:00', 'created_at' => null]);
        // A free registration through storeTicket.
        $this->addLoungePurchase('4237', self::BUYER, ['amount' => 0]);
        // A paid row that was deleted.
        $this->addLoungePurchase('4237', self::BUYER, ['deleted_at' => now()->subMinutes(10)->format('Y-m-d H:i:s')]);
        // A paid row for another event.
        $this->addLoungePurchase('4238', self::BUYER);
        // A paid booking of this event ten days earlier: it cannot be this payment's record.
        $this->addLoungePurchase('4237', self::BUYER, ['created_at' => now()->subDays(10)->format('Y-m-d H:i:s')]);

        $intent = $this->loungeIntent('pi_paris_unrecorded');
        $this->stripeHttp->intents['acct_paris'] = [$intent];
        $this->stripeHttp->sessions['acct_paris'] = [$this->checkoutSession('cs_paris_unrecorded', $intent, self::APP_SUCCESS_URL)];

        $this->runCheck()
            ->expectsOutputToContain('Lounge app/web: 1, with a Lounge purchase record: 0;')
            ->assertExitCode(0);

        $alert = $this->sentAlerts()[0];
        $this->assertSame(['pi_paris_unrecorded'], array_column($alert->missed, 'payment_intent'));
        // Guest, free and the earlier paid row; not the deleted one, not the other event's.
        $this->assertSame(3, $alert->missed[0]['lounge_places']);
        $alert->assertSeeInText('Note: the Lounge already has 3 places for this email at this event (paid, free or guest).');
    }

    public function test_a_web_payment_with_and_without_a_purchase_row(): void
    {
        // Checkout put no email on the card: only the session has the one she typed.
        $recorded = $this->loungeIntent('pi_web_recorded', ['ticket_type' => 'Online'], ['billing_details' => ['email' => null, 'name' => null]], ['created' => $this->minutesAgo(70)]);
        $orphan = $this->loungeIntent('pi_web_orphan', ['ticket_type' => 'Online'], ['billing_details' => ['email' => null, 'name' => null]]);

        $this->addLoungePurchase('4237', 'web.buyer@example.test');

        $this->stripeHttp->intents['acct_paris'] = [$orphan, $recorded];
        $this->stripeHttp->sessions['acct_paris'] = [
            $this->checkoutSession('cs_web_orphan', $orphan, self::WEB_SUCCESS_URL, ['customer_details' => ['email' => 'Web.Other@example.test', 'name' => 'Web Other']]),
            $this->checkoutSession('cs_web_recorded', $recorded, self::WEB_SUCCESS_URL, ['customer_details' => ['email' => 'web.buyer@example.test', 'name' => 'Web Buyer']]),
        ];

        $this->runCheck()
            ->expectsOutputToContain('Lounge app/web: 2, with a Lounge purchase record: 1;')
            ->expectsOutputToContain('NEW pi_web_orphan event 4237 on acct_paris (Lounge web booking (app.globalwomanlounge.com))')
            ->assertExitCode(0);

        $alert = $this->sentAlerts()[0];
        $this->assertSame(['pi_web_orphan'], array_column($alert->missed, 'payment_intent'));
        $this->assertSame(EventPaymentLookup::LOUNGE_WEB, $alert->missed[0]['source']);
        $alert->assertSeeInText('Booked in:      Lounge web booking (app.globalwomanlounge.com)');
        $alert->assertSeeInText('Ticket:         Online');
        $alert->assertSeeInText('Buyer email:    web.other@example.test');
        $alert->assertSeeInText('Buyer name:     Web Other');
        $alert->assertSeeInText('Checkout:       cs_web_orphan');
    }

    public function test_an_events_site_payment_is_unchanged_beside_a_recorded_lounge_payment(): void
    {
        Log::spy();
        $this->addLoungePurchase('4237', self::BUYER);
        $this->stripeHttp->intents['acct_paris'] = [$this->loungeIntent('pi_paris_recorded')];
        $this->stripeHttp->intents['platform'] = [[
            'id' => 'pi_site_orphan',
            'object' => 'payment_intent',
            'status' => 'succeeded',
            'amount' => 3000,
            'amount_received' => 3000,
            'currency' => 'eur',
            'created' => $this->minutesAgo(60),
            'receipt_email' => null,
            'metadata' => ['event_id' => '4237', 'event_title' => 'Mastermind & Networking', 'event_date' => '2026-10-29 08:30:00', 'email' => 'site.buyer@example.test'],
            'latest_charge' => ['id' => 'ch_site_orphan', 'object' => 'charge', 'created' => $this->minutesAgo(59), 'refunded' => false, 'metadata' => [], 'billing_details' => ['email' => null, 'name' => null], 'receipt_email' => null],
        ]];

        $this->runCheck()
            ->expectsOutputToContain('NEW pi_site_orphan event 4237 on platform')
            ->expectsOutputToContain('events site matched to an order: 0; Lounge app/web: 1, with a Lounge purchase record: 1;')
            ->assertExitCode(0);

        $alert = $this->sentAlerts()[0];
        $this->assertSame(['pi_site_orphan'], array_column($alert->missed, 'payment_intent'));
        $this->assertSame('events_site', $alert->missed[0]['kind']);
        $this->assertSame('Action needed: 1 paid event booking with no order', $alert->envelope()->subject);
        $alert->assertSeeInText('1 paid event booking on globalwomanevents.com has no order.');
        $alert->assertSeeInText('add this buyer to the event manually; do not ask her to rebook');
        $alert->assertDontSeeInText('Global Woman Lounge');
        $alert->assertDontSeeInText('Booked in:');

        Log::shouldHaveReceived('error')->withArgs(fn ($message, $context) => $message === 'Paid event booking with no order'
            && $context === ['payment_intent' => 'pi_site_orphan', 'account' => 'platform', 'event_id' => '4237'])->once();
        Log::shouldNotHaveReceived('error', ['Paid Lounge event booking with no purchase record', \Mockery::any()]);
    }

    public function test_an_events_site_and_a_lounge_payment_are_reported_together(): void
    {
        $lounge = $this->loungeIntent('pi_paris_app');
        $this->stripeHttp->intents['acct_paris'] = [$lounge];
        $this->stripeHttp->sessions['acct_paris'] = [$this->checkoutSession('cs_paris_app', $lounge, self::APP_SUCCESS_URL)];
        $this->stripeHttp->intents['platform'] = [array_replace($this->loungeIntent('pi_site_orphan'), [
            'metadata' => ['event_id' => '4237', 'event_title' => 'Mastermind & Networking', 'event_date' => '2026-10-29 08:30:00', 'email' => 'site.buyer@example.test'],
        ])];

        $this->runCheck()->assertExitCode(0);

        $alert = $this->sentAlerts()[0];
        $this->assertEqualsCanonicalizing(['pi_paris_app', 'pi_site_orphan'], array_column($alert->missed, 'payment_intent'));
        $this->assertSame('Action needed: 2 paid event bookings with no order or ticket record', $alert->envelope()->subject);
        $alert->assertSeeInText('1 paid event booking on globalwomanevents.com has no order.');
        $alert->assertSeeInText('1 paid event booking made in the Global Woman Lounge app or website has no ticket record.');
        $alert->assertSeeInText('Booked in:      Lounge app booking');
    }

    public function test_a_lounge_payment_is_reported_once(): void
    {
        $intent = $this->loungeIntent('pi_paris_once');
        $this->stripeHttp->intents['acct_paris'] = [$intent];
        $this->stripeHttp->sessions['acct_paris'] = [$this->checkoutSession('cs_paris_once', $intent, self::APP_SUCCESS_URL)];

        $this->runCheck()->assertExitCode(0);
        $this->runCheck()
            ->expectsOutputToContain('With no order: 1 (0 not reported before).')
            ->assertExitCode(0);

        $this->assertCount(1, $this->sentAlerts());

        // A new one later is reported, on its own.
        $later = $this->loungeIntent('pi_paris_later', [], ['billing_details' => ['email' => 'second.member@example.test']], ['created' => $this->minutesAgo(30)]);
        array_unshift($this->stripeHttp->intents['acct_paris'], $later);
        $this->stripeHttp->sessions['acct_paris'][] = $this->checkoutSession('cs_paris_later', $later, self::APP_SUCCESS_URL, ['customer_details' => ['email' => 'second.member@example.test', 'name' => null]]);

        $this->runCheck()->assertExitCode(0);

        $alerts = $this->sentAlerts();
        $this->assertCount(2, $alerts);
        $this->assertSame(['pi_paris_later'], array_column($alerts[1]->missed, 'payment_intent'));
    }

    public function test_a_dry_run_sends_and_remembers_nothing(): void
    {
        $intent = $this->loungeIntent('pi_paris_dry');
        $this->stripeHttp->intents['acct_paris'] = [$intent];
        $this->stripeHttp->sessions['acct_paris'] = [$this->checkoutSession('cs_paris_dry', $intent, self::APP_SUCCESS_URL)];

        $this->artisan('events:check-missed-payments', ['--dry-run' => true])
            ->expectsOutputToContain('NEW pi_paris_dry event 4237 on acct_paris (Lounge app booking)')
            ->expectsOutputToContain('Dry run: no email sent, nothing remembered.')
            ->assertExitCode(0);
        Mail::assertNothingSent();

        $this->runCheck()->assertExitCode(0);
        $this->assertCount(1, $this->sentAlerts());
        $this->assertSame(['pi_paris_dry'], array_column($this->sentAlerts()[0]->missed, 'payment_intent'));
    }

    public function test_one_purchase_row_covers_one_payment_so_a_second_payment_is_reported(): void
    {
        // She paid twice for the same booking; the app saved one of them.
        $first = $this->loungeIntent('pi_paris_first', [], [], ['created' => $this->minutesAgo(90)]);
        $second = $this->loungeIntent('pi_paris_second', [], [], ['created' => $this->minutesAgo(60)]);
        $this->addLoungePurchase('4237', self::BUYER);

        $this->stripeHttp->intents['acct_paris'] = [$second, $first];
        $this->stripeHttp->sessions['acct_paris'] = [
            $this->checkoutSession('cs_paris_second', $second, self::APP_SUCCESS_URL),
            $this->checkoutSession('cs_paris_first', $first, self::APP_SUCCESS_URL),
        ];

        $this->runCheck()
            ->expectsOutputToContain('Lounge app/web: 2, with a Lounge purchase record: 1;')
            ->assertExitCode(0);

        $alert = $this->sentAlerts()[0];
        $this->assertSame(['pi_paris_second'], array_column($alert->missed, 'payment_intent'));
        $alert->assertSeeInText('Note: the Lounge already has 1 place for this email at this event');
        $alert->assertSeeInText('this may be a second payment for one booking');
    }

    public function test_a_lounge_checkout_charged_on_the_platform_is_found_through_its_session(): void
    {
        // Kent is not onboarded, so createEventSession charged the platform and put the metadata
        // on the session only: the PaymentIntent has none.
        $this->addClub('kent', null, false);
        $kentMetadata = ['event_id' => '4240', 'club_id' => '12', 'from_app' => 'yes', 'ticket_type' => 'In-Person', 'is_bring_friend' => 'false'];

        $orphan = $this->loungeIntent('pi_kent_orphan', [], ['billing_details' => ['email' => 'kent.member@example.test']], ['metadata' => []]);
        $recorded = $this->loungeIntent('pi_kent_recorded', [], ['billing_details' => ['email' => 'kent.recorded@example.test']], ['metadata' => [], 'created' => $this->minutesAgo(80)]);
        $this->addLoungePurchase('4240', 'kent.recorded@example.test');

        $this->stripeHttp->intents['platform'] = [$orphan, $recorded];
        $this->stripeHttp->sessions['platform'] = [
            $this->checkoutSession('cs_kent_orphan', $orphan, self::APP_SUCCESS_URL, ['metadata' => $kentMetadata, 'customer_details' => ['email' => 'kent.member@example.test', 'name' => 'Kent Member']]),
            $this->checkoutSession('cs_kent_recorded', $recorded, self::APP_SUCCESS_URL, ['metadata' => $kentMetadata, 'customer_details' => ['email' => 'kent.recorded@example.test', 'name' => null]]),
            // An expired checkout is not a payment.
            $this->checkoutSession('cs_kent_expired', $this->loungeIntent('pi_kent_never', [], [], ['status' => 'requires_payment_method', 'metadata' => []]), self::APP_SUCCESS_URL, ['metadata' => $kentMetadata, 'status' => 'expired', 'payment_status' => 'unpaid']),
        ];

        $this->runCheck()
            ->expectsOutputToContain('Succeeded payments: 2 (events site matched to an order: 0; Lounge app/web: 2, with a Lounge purchase record: 1; other: 0;')
            ->expectsOutputToContain('NEW pi_kent_orphan event 4240 on platform (Lounge app booking)')
            ->assertExitCode(0);

        $alert = $this->sentAlerts()[0];
        $this->assertSame(['pi_kent_orphan'], array_column($alert->missed, 'payment_intent'));
        $alert->assertSeeInText('Kent Breakfast (event 4240)');
        $alert->assertSeeInText('Club:           kent');
        $alert->assertSeeInText('Stripe account: platform account');
        $alert->assertSeeInText('Checkout:       cs_kent_orphan');

        // One list call on the platform, in the window, expanded down to the charge.
        $lists = array_values(array_filter($this->sessionRequests('platform'), fn (array $r) => isset($r['params']['created'])));
        $this->assertCount(1, $lists);
        $this->assertSame('complete', $lists[0]['params']['status']);
        $this->assertSame(['data.payment_intent.latest_charge'], $lists[0]['params']['expand']);
        $this->assertSame(Carbon::now()->subHours(48)->getTimestamp(), $lists[0]['params']['created']['gte']);
    }

    public function test_a_bring_a_friend_payment_names_the_friend(): void
    {
        $intent = $this->loungeIntent('pi_paris_friend', [
            'is_bring_friend' => 'true',
            'friend_first_name' => 'Claire',
            'friend_last_name' => 'Amie',
            'friend_email' => 'Claire.Amie@example.test',
        ]);
        $this->stripeHttp->intents['acct_paris'] = [$intent];
        $this->stripeHttp->sessions['acct_paris'] = [$this->checkoutSession('cs_paris_friend', $intent, self::APP_SUCCESS_URL)];

        $this->runCheck()->assertExitCode(0);

        $this->sentAlerts()[0]->assertSeeInText('Bring a friend: yes, so her friend has no place either (Claire Amie claire.amie@example.test)');
    }

    public function test_recent_and_refunded_lounge_payments_are_left_alone(): void
    {
        $this->stripeHttp->intents['acct_paris'] = [
            // Paid 5 minutes ago: the app may still be saving it.
            $this->loungeIntent('pi_paris_recent', [], ['created' => $this->minutesAgo(5)], ['created' => $this->minutesAgo(40)]),
            // Refunded in full.
            $this->loungeIntent('pi_paris_refunded', [], ['refunded' => true]),
        ];

        $this->runCheck()
            ->expectsOutputToContain('Lounge app/web: 2, with a Lounge purchase record: 0; other: 0; too recent: 1; refunded: 1')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_a_payment_whose_checkout_session_cannot_be_read_is_still_reported(): void
    {
        $this->stripeHttp->intents['acct_paris'] = [$this->loungeIntent('pi_paris_nosession')];
        $this->stripeHttp->sessionFailures['acct_paris'] = [500, 'api_error', 'Stripe had a problem'];

        $this->runCheck()
            ->expectsOutputToContain('NEW pi_paris_nosession event 4237 on acct_paris (Lounge booking, app or web')
            ->assertExitCode(0);

        $alert = $this->sentAlerts()[0];
        $this->assertSame(EventPaymentLookup::LOUNGE_UNKNOWN, $alert->missed[0]['source']);
        $alert->assertSeeInText('Booked in:      Lounge booking, app or web (its Stripe checkout could not be identified)');
        $alert->assertSeeInText('its Stripe checkout session could not be read');
        $alert->assertSeeInText('Buyer email:    '.self::BUYER);
    }

    public function test_when_the_purchase_records_cannot_be_read_nothing_is_alerted_as_missed_and_the_check_says_so(): void
    {
        Schema::connection('events_test')->drop('Wo_Clickfunnel_Event_Purchase');
        $this->stripeHttp->intents['acct_paris'] = [$this->loungeIntent('pi_paris_unknown')];

        $this->runCheck()
            ->expectsOutputToContain('With no order: 0 (0 not reported before).')
            ->expectsOutputToContain('COULD NOT CHECK Lounge purchase records (Wo_Clickfunnel_Event_Purchase), so no Lounge app or web booking was checked: QueryException')
            ->assertExitCode(0);

        $alert = $this->sentAlerts()[0];
        $this->assertSame([], $alert->missed);
        $alert->assertSeeInText('- Lounge purchase records (Wo_Clickfunnel_Event_Purchase), so no Lounge app or web booking was checked: QueryException');
    }
}
