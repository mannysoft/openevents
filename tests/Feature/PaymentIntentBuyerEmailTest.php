<?php

namespace Tests\Feature;

use App\Services\EventPaymentLookup;
use App\Services\StripeGateway;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Log;
use Tests\Concerns\BootsWithoutDatabase;
use Tests\Concerns\UsesEventPaymentTables;

/**
 * The buyer's email goes on the PaymentIntent, not only on the charge (which gets it only when
 * she comes back to the site), and the already-purchased check looks there.
 */
class PaymentIntentBuyerEmailTest extends TestCase
{
    use BootsWithoutDatabase;
    use UsesEventPaymentTables;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEventPaymentTables();
        $this->app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $this->app->forgetInstance('encrypter');
    }

    protected function tearDown(): void
    {
        $this->tearDownEventPaymentTables();

        parent::tearDown();
    }

    private function gateway(?string $clubSlug): StripeGateway
    {
        $gateway = new StripeGateway;
        $gateway->clubSlug = $clubSlug;
        $gateway->eventId = 4237;
        $gateway->eventTitle = 'Mastermind & Networking';
        $gateway->eventDate = '2026-10-29 08:30:00';
        $gateway->currencyCode = 'EUR';

        return $gateway;
    }

    public function test_a_known_email_is_on_the_payment_intent_when_it_is_created_on_the_platform(): void
    {
        $this->addClub('paris', null, false);
        session(['checkout_email' => '  Buyer.One@Example.TEST ']);

        $this->gateway('paris')->getClientSecret(3000);

        $create = $this->stripeHttp->requestsTo('post', '#^/v1/payment_intents$#');
        $this->assertCount(1, $create);
        $this->assertSame('platform', $create[0]['account']);
        $this->assertSame('buyer.one@example.test', $create[0]['params']['metadata']['email']);
        $this->assertSame('4237', (string) $create[0]['params']['metadata']['event_id']);
        $this->assertSame('platform', session('stripePaymentIntentAccount.paris'));
    }

    public function test_a_known_email_is_on_the_payment_intent_when_it_is_created_on_the_club_account(): void
    {
        $this->addClub('paris', 'acct_paris');
        session(['email' => 'signed.in@example.test']);

        $gateway = $this->gateway('paris');
        $gateway->stripeAccountId = 'acct_paris';
        $gateway->getClientSecret(3000, 'acct_paris');

        $create = $this->stripeHttp->requestsTo('post', '#^/v1/payment_intents$#');
        $this->assertSame('acct_paris', $create[0]['account']);
        $this->assertSame('signed.in@example.test', $create[0]['params']['metadata']['email']);
        $this->assertSame('acct_paris', session('stripePaymentIntentAccount.paris'));
    }

    public function test_no_email_key_is_sent_when_none_is_known(): void
    {
        $this->addClub('paris', null, false);
        session(['checkout_email' => 'half-typed@']);

        $this->gateway('paris')->getClientSecret(3000);

        $create = $this->stripeHttp->requestsTo('post', '#^/v1/payment_intents$#');
        $this->assertArrayNotHasKey('email', $create[0]['params']['metadata']);
    }

    public function test_an_email_typed_after_the_payment_intent_exists_is_added_to_it_once(): void
    {
        $session = [
            'stripeClientSecret' => ['paris' => 'pi_page_secret_x'],
            'stripePaymentIntentId' => ['paris' => 'pi_page'],
            'stripePaymentIntentAccount' => ['paris' => 'acct_paris'],
        ];

        $this->withSession($session)
            ->post(route('setUserSession'), ['email' => 'Typed.Later@example.test', 'club' => 'paris'])
            ->assertOk();
        // The page posts on every keystroke: the same email is not sent again.
        $this->post(route('setUserSession'), ['email' => 'typed.later@example.test', 'club' => 'paris'])->assertOk();

        $updates = $this->stripeHttp->requestsTo('post', '#^/v1/payment_intents/pi_page$#');
        $this->assertCount(1, $updates);
        $this->assertSame('acct_paris', $updates[0]['account']);
        $this->assertSame(['metadata' => ['email' => 'typed.later@example.test']], $updates[0]['params']);

        // A corrected email is sent.
        $this->post(route('setUserSession'), ['email' => 'corrected@example.test', 'club' => 'paris'])->assertOk();
        $updates = $this->stripeHttp->requestsTo('post', '#^/v1/payment_intents/pi_page$#');
        $this->assertCount(2, $updates);
        $this->assertSame('corrected@example.test', $updates[1]['params']['metadata']['email']);
    }

    public function test_a_platform_payment_intent_is_updated_without_a_connected_account(): void
    {
        $this->withSession([
            'stripePaymentIntentId' => ['paris' => 'pi_platform_page'],
            'stripePaymentIntentAccount' => ['paris' => 'platform'],
        ])->post(route('setUserSession'), ['email' => 'buyer@example.test', 'club' => 'paris'])->assertOk();

        $updates = $this->stripeHttp->requestsTo('post', '#^/v1/payment_intents/pi_platform_page$#');
        $this->assertCount(1, $updates);
        $this->assertSame('platform', $updates[0]['account']);
    }

    public function test_nothing_is_sent_without_a_payment_intent_its_account_or_a_valid_email(): void
    {
        $this->withSession(['stripePaymentIntentId' => ['paris' => 'pi_unknown_account']])
            ->post(route('setUserSession'), ['email' => 'buyer@example.test', 'club' => 'paris'])->assertOk();
        $this->withSession(['stripePaymentIntentId' => ['paris' => 'pi_x'], 'stripePaymentIntentAccount' => ['paris' => 'platform']])
            ->post(route('setUserSession'), ['email' => 'not-yet-an-email', 'club' => 'paris'])->assertOk();

        $this->assertSame([], $this->stripeHttp->requestsTo('post', '#^/v1/payment_intents#'));
    }

    public function test_a_stripe_error_while_tagging_is_logged_and_does_not_break_the_page(): void
    {
        Log::spy();
        $this->stripeHttp->failures['acct_paris'] = [403, 'invalid_request_error', 'No access'];

        $this->withSession([
            'stripePaymentIntentId' => ['paris' => 'pi_page'],
            'stripePaymentIntentAccount' => ['paris' => 'acct_paris'],
        ])->post(route('setUserSession'), ['email' => 'buyer@example.test', 'club' => 'paris'])
            ->assertOk()
            ->assertExactJson(['message' => 'Session variables set']);

        Log::shouldHaveReceived('warning')->withArgs(fn ($message) => $message === 'Could not add the buyer email to PaymentIntent pi_page: PermissionException')->once();
    }

    public function test_the_already_purchased_check_finds_a_payment_on_the_club_account(): void
    {
        $this->addClub('paris', 'acct_paris');
        $this->stripeHttp->searchResults['acct_paris'] = [['id' => 'pi_paid_never_returned', 'object' => 'payment_intent', 'status' => 'succeeded']];

        $found = (new EventPaymentLookup)->succeededPaymentFor(' Buyer.One@example.test', 4237, 'paris');

        $this->assertSame('pi_paid_never_returned', $found);
        $searches = $this->stripeHttp->requestsTo('get', '#^/v1/payment_intents/search$#');
        $this->assertSame(['platform', 'acct_paris'], array_column($searches, 'account'));
        $this->assertSame(
            "status:'succeeded' AND metadata['event_id']:'4237' AND metadata['email']:'buyer.one@example.test'",
            $searches[0]['params']['query']
        );
        $this->assertSame(10, $searches[0]['params']['limit']);
        $this->assertSame(['data.latest_charge'], $searches[0]['params']['expand']);
    }

    /**
     * A succeeded PaymentIntent as search returns it, with latest_charge expanded.
     */
    private function paid(string $id, bool $refunded = false): array
    {
        return [
            'id' => $id,
            'object' => 'payment_intent',
            'status' => 'succeeded',
            'latest_charge' => ['id' => 'ch_'.substr($id, 3), 'object' => 'charge', 'refunded' => $refunded],
        ];
    }

    public function test_her_own_checkout_payment_never_counts_as_already_purchased(): void
    {
        $this->addClub('paris', 'acct_paris');
        // She has paid for the checkout in progress, and search has already indexed it.
        session(['stripePaymentIntentId' => ['paris' => 'pi_this_checkout']]);
        $this->stripeHttp->searchResults['acct_paris'] = [$this->paid('pi_this_checkout')];

        $this->assertNull((new EventPaymentLookup)->succeededPaymentFor('buyer@example.test', 4237, 'paris'));
    }

    public function test_an_earlier_payment_behind_her_own_is_still_found(): void
    {
        $this->addClub('paris', 'acct_paris');
        session(['stripePaymentIntentId' => ['paris' => 'pi_this_checkout']]);
        $this->stripeHttp->searchResults['acct_paris'] = [$this->paid('pi_this_checkout'), $this->paid('pi_earlier_booking')];

        $this->assertSame('pi_earlier_booking', (new EventPaymentLookup)->succeededPaymentFor('buyer@example.test', 4237, 'paris'));
    }

    public function test_her_own_checkout_is_recognised_under_the_club_fallback_slot(): void
    {
        // getClientSecret() keeps a PaymentIntent with no club slug under 'club'.
        session(['stripePaymentIntentId' => ['club' => 'pi_this_checkout']]);
        $this->stripeHttp->searchResults['platform'] = [$this->paid('pi_this_checkout')];

        $this->assertNull((new EventPaymentLookup)->succeededPaymentFor('buyer@example.test', 4237, null));

        $this->stripeHttp->searchResults['platform'][] = $this->paid('pi_earlier_booking');
        $this->assertSame('pi_earlier_booking', (new EventPaymentLookup)->succeededPaymentFor('buyer@example.test', 4237, null));
    }

    public function test_a_payment_refunded_in_full_does_not_stop_her_booking_again(): void
    {
        $this->addClub('paris', 'acct_paris');
        $this->stripeHttp->searchResults['platform'] = [$this->paid('pi_refunded', refunded: true)];
        $this->stripeHttp->searchResults['acct_paris'] = [$this->paid('pi_refunded_too', refunded: true)];

        $this->assertNull((new EventPaymentLookup)->succeededPaymentFor('buyer@example.test', 4237, 'paris'));

        // A partly refunded payment (refunded stays false) still counts.
        $this->stripeHttp->searchResults['acct_paris'][] = $this->paid('pi_partly_refunded');
        $this->assertSame('pi_partly_refunded', (new EventPaymentLookup)->succeededPaymentFor('buyer@example.test', 4237, 'paris'));
    }

    public function test_the_already_purchased_check_finds_nothing_and_survives_a_stripe_error(): void
    {
        Log::spy();
        $this->addClub('paris', 'acct_paris');
        $this->stripeHttp->failures['platform'] = [500, 'api_error', 'Stripe is down'];

        $this->assertNull((new EventPaymentLookup)->succeededPaymentFor('buyer@example.test', 4237, 'paris'));
        $this->assertCount(2, $this->stripeHttp->requestsTo('get', '#^/v1/payment_intents/search$#'));
        Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context) => $context === ['event_id' => '4237', 'account' => 'platform', 'error' => 'UnknownApiErrorException'])->once();

        // No usable email: Stripe is not asked.
        $this->assertNull((new EventPaymentLookup)->succeededPaymentFor(null, 4237, 'paris'));
        $this->assertCount(2, $this->stripeHttp->requestsTo('get', '#^/v1/payment_intents/search$#'));
    }

    public function test_quotes_in_an_email_cannot_break_out_of_the_search_string(): void
    {
        (new EventPaymentLookup)->succeededPaymentFor("o'brien@example.test", 4237, null);

        $search = $this->stripeHttp->requestsTo('get', '#^/v1/payment_intents/search$#')[0];
        $this->assertStringEndsWith("metadata['email']:'o\\'brien@example.test'", $search['params']['query']);
    }
}
