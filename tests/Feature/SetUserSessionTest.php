<?php

namespace Tests\Feature;

use App\Http\Controllers\SessionController;
use App\Models\Event;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase;
use Mockery\MockInterface;
use Tests\Concerns\BootsWithoutDatabase;

/**
 * The checkout page keeps the buyer's name, email and phone in the session as they type.
 * Those details travel in a POST body with the CSRF token, never in a URL, where browser
 * history, Referer headers and server logs would keep them.
 */
class SetUserSessionTest extends TestCase
{
    use BootsWithoutDatabase;

    private const TOKEN = 'fixture-csrf-token';

    private const BUYER = [
        'firstname' => 'Fixturefirst',
        'lastname' => 'Fixturelast',
        'email' => 'fixture.buyer+events@example.test',
        'phone' => '+44 7700 900123',
        'club' => 'london',
    ];

    private const SESSION_KEYS = ['checkout_firstname', 'checkout_lastname', 'checkout_email', 'checkout_phone'];

    private const AJAX = ['Accept' => '*/*', 'X-Requested-With' => 'XMLHttpRequest'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $this->app->forgetInstance('encrypter');

        // Laravel skips the CSRF check under PHPUnit; check tokens the way production does.
        $this->app->bind(ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
    }

    /**
     * The Stripe customer sync reaches Stripe and the Lounge database, so it is counted, not run.
     */
    private function expectStripeSync(int $times): void
    {
        $this->partialMock(SessionController::class, function (MockInterface $mock) use ($times) {
            $mock->shouldReceive('setStripeCustomer')->times($times);
        });
    }

    private function assertNoBuyerDetailsStored(): void
    {
        foreach (self::SESSION_KEYS as $key) {
            $this->assertFalse(session()->exists($key), "$key was stored");
        }
    }

    public function test_a_get_request_is_refused(): void
    {
        $this->expectStripeSync(0);

        $this->withSession(['_token' => self::TOKEN])
            ->get(route('setUserSession', self::BUYER), self::AJAX)
            ->assertStatus(405);

        $this->assertNoBuyerDetailsStored();
    }

    public function test_a_post_without_a_valid_csrf_token_is_refused(): void
    {
        $this->expectStripeSync(0);

        $this->withSession(['_token' => self::TOKEN])
            ->post(route('setUserSession'), self::BUYER, self::AJAX)
            ->assertStatus(419);
        $this->post(route('setUserSession'), self::BUYER + ['_token' => 'not-the-session-token'], self::AJAX)
            ->assertStatus(419);

        $this->assertNoBuyerDetailsStored();
    }

    public function test_a_valid_post_sets_the_same_session_keys_as_before(): void
    {
        $this->expectStripeSync(1);

        $this->withSession(['_token' => self::TOKEN])
            ->post(route('setUserSession'), self::BUYER + ['_token' => self::TOKEN], self::AJAX)
            ->assertOk()
            ->assertExactJson(['message' => 'Session variables set']);

        $this->assertSame('Fixturefirst', session('checkout_firstname'));
        $this->assertSame('Fixturelast', session('checkout_lastname'));
        $this->assertSame('fixture.buyer+events@example.test', session('checkout_email'));
        $this->assertSame('+44 7700 900123', session('checkout_phone'));
        $this->assertEqualsCanonicalizing(
            self::SESSION_KEYS,
            array_values(array_diff(array_keys(session()->all()), ['_token', '_flash'])),
            'the endpoint must set exactly the four checkout keys'
        );
    }

    public function test_blank_and_missing_fields_are_stored_as_null_as_before(): void
    {
        $this->expectStripeSync(1);

        $this->withSession(['_token' => self::TOKEN])
            ->post(route('setUserSession'), ['_token' => self::TOKEN, 'firstname' => '', 'email' => '   '], self::AJAX)
            ->assertOk();

        foreach (self::SESSION_KEYS as $key) {
            $this->assertTrue(session()->exists($key), "$key was not set");
            $this->assertNull(session($key), "$key should be null");
        }
    }

    public function test_details_in_the_query_string_are_ignored(): void
    {
        $this->expectStripeSync(1);

        $this->withSession(['_token' => self::TOKEN])
            ->post(route('setUserSession', self::BUYER), ['_token' => self::TOKEN], self::AJAX)
            ->assertOk();

        foreach (self::SESSION_KEYS as $key) {
            $this->assertNull(session($key), "$key was read from the URL");
        }
    }

    public function test_over_long_or_non_string_fields_are_refused(): void
    {
        $this->expectStripeSync(0);
        $this->withSession(['_token' => self::TOKEN]);

        $invalid = [
            ['firstname' => str_repeat('a', 101)],
            ['lastname' => str_repeat('a', 101)],
            ['email' => str_repeat('a', 243).'@example.test'],
            ['phone' => str_repeat('1', 33)],
            ['club' => str_repeat('a', 101)],
            ['firstname' => ['Fixturefirst']],
        ];

        foreach ($invalid as $fields) {
            $this->post(route('setUserSession'), array_merge(self::BUYER, $fields, ['_token' => self::TOKEN]), self::AJAX)
                ->assertStatus(422)
                ->assertJsonValidationErrors(array_keys($fields));
        }

        $this->assertNoBuyerDetailsStored();
    }

    public function test_fields_at_the_length_caps_are_accepted(): void
    {
        $this->expectStripeSync(1);

        $this->withSession(['_token' => self::TOKEN])
            ->post(route('setUserSession'), [
                '_token' => self::TOKEN,
                'firstname' => str_repeat('é', 100),
                'lastname' => str_repeat('a', 100),
                'email' => str_repeat('a', 241).'@example.test',
                'phone' => str_repeat('1', 32),
                'club' => str_repeat('a', 100),
            ], self::AJAX)
            ->assertOk();

        $this->assertSame(str_repeat('é', 100), session('checkout_firstname'));
        $this->assertSame(254, strlen(session('checkout_email')));
    }

    public function test_the_checkout_page_posts_the_details_in_the_body_with_the_csrf_token(): void
    {
        session()->put('_token', self::TOKEN);
        $event = (new Event)->forceFill(['id' => 4321, 'club' => 'london']);

        $html = view('Public.ViewEvent.Partials.new_stripe', [
            'stripeAccountId' => null,
            'clientSecret' => 'pi_fixture_secret_fixture',
            'event' => $event,
        ])->render();

        $this->assertSame(1, preg_match('/function setuserdetails\(\) \{(.*?)\n    \}/s', $html, $m));
        $call = $m[1];

        $this->assertStringContainsString('url: "'.route('setUserSession').'",', $call);
        $this->assertStringContainsString("type: 'POST',", $call);
        $this->assertStringContainsString("_token: '".self::TOKEN."',", $call);
        foreach (['firstname', 'lastname', 'email', 'phone'] as $field) {
            $this->assertStringContainsString("$field: \$('input[name=\"$field\"]').val(),", $call);
        }
        $this->assertStringContainsString("club: 'london'", $call);
        $this->assertStringNotContainsString('?', $call, 'nothing may be appended to the URL');
        $this->assertStringNotContainsString('processData', $call, 'processData: false would send the object unencoded');
    }
}
