<?php

namespace Tests\Feature;

use App\Gateways\EventGateway;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BootsWithoutDatabase;

/**
 * A guest who is not signed in registers before she can book: "Book my spot" on a club event
 * page (saveUserData), and the order form (postCreateOrder, which calls EventGateway::createUser).
 * Both used to sign in to the Lobby, look her up and make her a Lobby account, with no time
 * limit and nothing to catch an error. While the Lobby was off, every guest met a server error.
 * Now she carries on with no Lobby account.
 *
 * No request leaves the machine. Every Lobby call is made by a Guzzle client resolved from the
 * container (LobbyAuth::httpClient()), and these tests bind that client to a scripted handler.
 * A request the script does not expect fails the test; it never reaches the network.
 */
class BookingWithoutLobbyTest extends TestCase
{
    use BootsWithoutDatabase;

    private const EVENT_ID = 3101;

    private const GUEST = [
        'email' => 'fixture.guest@example.test',
        'firstName' => 'Fixturefirst',
        'lastName' => 'Fixturelast',
        'mobileNo' => '+447700900123',
        'eventId' => self::EVENT_ID,
        'lobbyeventId' => '',
        'club' => 'london',
    ];

    /** Reminder emails add_email_schedule() makes for an event with a date. */
    private const REMINDERS = 10;

    private const MOST_CONNECT_SECONDS = 3;

    private const MOST_SECONDS = 5;

    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface, options: array}> */
    private array $lobbyRequests = [];

    /** @var array<int, MessageLogged> */
    private array $warnings = [];

    /** @var array<int, MessageLogged> every level */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $this->app->forgetInstance('encrypter');

        config([
            'database.connections.events_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.default' => 'events_test',
        ]);
        DB::purge('events_test');

        $this->createRegistrationTables();

        DB::table('events')->insert([
            'id' => self::EVENT_ID,
            'title' => 'Fixture networking evening',
            'club' => 'london',
            'event_date' => now()->addDays(20)->format('Y-m-d'),
        ]);

        Event::listen(MessageLogged::class, function (MessageLogged $message) {
            $this->logged[] = $message;

            if ($message->level === 'warning') {
                $this->warnings[] = $message;
            }
        });
    }

    /**
     * The Lobby off, or broken in each way it can be: the guest's registration completes.
     */
    #[DataProvider('lobbyFailures')]
    public function test_book_my_spot_carries_on_without_the_lobby(array $answers): void
    {
        $this->lobbyAnswers($answers);

        $response = $this->postJson('/saveUserData', self::GUEST);

        $response->assertOk()->assertExactJson(['status' => 'success']);
        $this->assertSame(count($answers), count($this->lobbyRequests), 'every scripted Lobby answer was used, and no more');
        $this->assertSignedInWithoutLobbyAccount();
        $this->assertRegistrationStored();
        $this->assertOneWarningWithoutGuestDetails();
        $this->assertEveryLobbyRequestIsTimeLimited();
    }

    /**
     * postCreateOrder calls createUser() for a guest and ignores what it returns: only an
     * exception stopped the order. It must not throw.
     */
    #[DataProvider('lobbyFailures')]
    public function test_the_order_form_registration_carries_on_without_the_lobby(array $answers): void
    {
        $this->lobbyAnswers($answers);

        $gateway = new EventGateway();
        $gateway->email = self::GUEST['email'];
        $gateway->firstName = self::GUEST['firstName'];
        $gateway->lastName = self::GUEST['lastName'];
        $gateway->mobileNo = self::GUEST['mobileNo'];
        $gateway->eventId = self::EVENT_ID;
        $gateway->club = self::GUEST['club'];

        $result = $gateway->createUser();

        $this->assertSame(['status' => 'success'], $result->getData(true));
        $this->assertSignedInWithoutLobbyAccount();
        $this->assertRegistrationStored();
        $this->assertOneWarningWithoutGuestDetails();
    }

    public static function lobbyFailures(): array
    {
        $signedIn = fn () => new Response(200, ['Content-Type' => 'application/json'], '{"access_token":"fixture-lobby-token"}');
        $notFound = fn () => new Response(200, ['Content-Type' => 'application/json'], '{"api_status":400}');
        $timedOut = fn (string $path) => new ConnectException(
            'cURL error 28: Operation timed out',
            new PsrRequest('POST', 'https://lobby.globalwomanclub.com'.$path)
        );

        return [
            'sign-in answers 502 (the Lobby off, as now)' => [[new Response(502, [], '<html>502 Bad Gateway</html>')]],
            'sign-in answers 404' => [[new Response(404, [], '<html>Not Found</html>')]],
            'sign-in times out' => [[$timedOut('/api/auth')]],
            'sign-in answers a holding page with 200' => [[new Response(200, ['Content-Type' => 'text/html'], '<html>Back soon</html>')]],
            'sign-in works, the look-up answers 502' => [[$signedIn(), new Response(502, [], '<html>502 Bad Gateway</html>')]],
            'sign-in works, the look-up times out' => [[$signedIn(), $timedOut('/api/get-user-data-email')]],
            'not in the Lobby, the second sign-in answers 502' => [[$signedIn(), $notFound(), new Response(502, [], '')]],
            'not in the Lobby, making her account times out' => [[$signedIn(), $notFound(), $signedIn(), $timedOut('/api/create-account')]],
            'not in the Lobby, making her account answers 500' => [[$signedIn(), $notFound(), $signedIn(), new Response(500, [], '')]],
            'not in the Lobby, it answers 200 but makes no account' => [[$signedIn(), $notFound(), $signedIn(), self::refusal()]],
        ];
    }

    /** The Lobby with registration switched off: 200, and no account. */
    private static function refusal(): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], '{"api_status":400,"errors":{"error_text":"fixture-lobby-body"}}');
    }

    /**
     * The Lobby's answer used to be logged whole when it made no account. It can hold her
     * details, so nothing of it is logged now, at any level.
     */
    public function test_the_lobby_answer_is_not_logged(): void
    {
        $this->lobbyAnswers([
            new Response(200, [], '{"access_token":"fixture-lobby-token"}'),
            new Response(200, [], '{"api_status":400}'),
            new Response(200, [], '{"access_token":"fixture-lobby-token"}'),
            self::refusal(),
        ]);

        $this->postJson('/saveUserData', self::GUEST)->assertOk()->assertExactJson(['status' => 'success']);

        $this->assertSignedInWithoutLobbyAccount();
        foreach ($this->logged as $message) {
            $this->assertStringNotContainsString('fixture-lobby-body', $message->message.json_encode($message->context));
        }
    }

    /**
     * With the Lobby up, a guest the Lobby knows is registered exactly as before this change.
     */
    public function test_a_guest_the_lobby_knows_is_registered_as_before(): void
    {
        $this->lobbyAnswers([
            new Response(200, [], '{"access_token":"fixture-lobby-token"}'),
            new Response(200, [], '{"api_status":200,"user_data":{"user_id":4242}}'),
        ]);

        $this->postJson('/saveUserData', self::GUEST)
            ->assertOk()
            ->assertExactJson(['status' => 'success']);

        $this->assertSame('emailExists', session('access_token'));
        $this->assertSame(4242, session('ref_id'));
        $this->assertSame(self::GUEST['email'], session('email'));
        $this->assertCount(2, $this->lobbyRequests);
        $this->assertSame('/api/auth', $this->lobbyRequests[0]['request']->getUri()->getPath());
        $this->assertSame('/api/get-user-data-email', $this->lobbyRequests[1]['request']->getUri()->getPath());
        $this->assertSame([], $this->warnings);
        $this->assertRegistrationStored();
        $this->assertEveryLobbyRequestIsTimeLimited();
    }

    /**
     * On a Stripe Connect club's payment page the card is paid in the browser and the order is
     * made on Stripe's return. The button sits inside a form that posts to postCreateOrder;
     * that form must never be sent by the same click. Until now only frontend.js stopped it,
     * by accident: its submit handler calls preventDefault() and then fails on an undefined
     * `card`. The page's own handler now stops it first.
     */
    public function test_the_connect_payment_button_never_sends_the_order_form(): void
    {
        $view = file_get_contents(resource_path('views/Public/ViewEvent/Partials/new_stripe.blade.php'));

        $this->assertMatchesRegularExpression(
            "/getElementById\\('btn-paycard'\\)\\.onclick = async \\((\\w+)\\) => \\{\\s*(?:\\/\\/[^\\n]*\\n\\s*)*\\1\\.preventDefault\\(\\);/",
            $view,
            'the Connect pay button handler must call preventDefault() before anything else'
        );
        $this->assertLessThan(
            strpos($view, 'stripe.confirmPayment('),
            strpos($view, '.preventDefault();'),
            'preventDefault() must run before the card is confirmed'
        );
    }

    /** Every Lobby call goes through a client resolved here, answered by the script. */
    private function lobbyAnswers(array $answers): void
    {
        $stack = HandlerStack::create(new MockHandler($answers));
        $stack->push(Middleware::history($this->lobbyRequests));

        $this->app->bind(Client::class, fn ($app, array $parameters) => new Client(
            ['handler' => $stack] + ($parameters['config'] ?? [])
        ));
    }

    private function assertSignedInWithoutLobbyAccount(): void
    {
        $this->assertSame(EventGateway::NO_LOBBY_ACCOUNT, session('access_token'));
        $this->assertNull(session('ref_id'));
        $this->assertSame(self::GUEST['email'], session('email'));
        $this->assertSame(self::GUEST['firstName'], session('firstname'));
        $this->assertSame(self::GUEST['lastName'], session('lastname'));
        $this->assertSame(self::GUEST['mobileNo'], session('phone'));
        $this->assertSame('0', session('member'));
    }

    private function assertRegistrationStored(): void
    {
        $this->assertSame(1, DB::table('Wo_Clickfunnel_Event_Data_New')
            ->where('email_id', self::GUEST['email'])
            ->where('event_id', self::EVENT_ID)
            ->count());
        $this->assertSame(self::REMINDERS, DB::table('email_schedules')
            ->where('email', self::GUEST['email'])
            ->where('event_id', self::EVENT_ID)
            ->count());
    }

    private function assertOneWarningWithoutGuestDetails(): void
    {
        $this->assertCount(1, $this->warnings);
        $warning = $this->warnings[0];
        $this->assertStringContainsString('Lobby unavailable', $warning->message);
        $this->assertSame(self::EVENT_ID, $warning->context['event_id']);

        $logged = $warning->message.json_encode($warning->context);
        foreach (['email', 'firstName', 'lastName', 'mobileNo'] as $field) {
            $this->assertStringNotContainsString(self::GUEST[$field], $logged, "$field was logged");
        }
    }

    private function assertEveryLobbyRequestIsTimeLimited(): void
    {
        $this->assertNotEmpty($this->lobbyRequests);

        foreach ($this->lobbyRequests as $entry) {
            $this->assertSame('lobby.globalwomanclub.com', $entry['request']->getUri()->getHost());
            $this->assertGreaterThan(0, $entry['options']['connect_timeout'] ?? 0);
            $this->assertLessThanOrEqual(self::MOST_CONNECT_SECONDS, $entry['options']['connect_timeout']);
            $this->assertGreaterThan(0, $entry['options']['timeout'] ?? 0);
            $this->assertLessThanOrEqual(self::MOST_SECONDS, $entry['options']['timeout']);
        }
    }

    private function createRegistrationTables(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('club')->nullable();
            $table->date('event_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('email_schedules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile_no')->nullable();
            $table->unsignedInteger('event_id')->nullable();
            $table->string('club')->nullable();
            $table->integer('type')->nullable();
            $table->string('date_send')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('Wo_Clickfunnel_Event_Data_New', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email_id')->nullable();
            $table->string('full_name')->nullable();
            $table->string('mobile_no')->nullable();
            $table->dateTime('created')->nullable();
            $table->string('club')->nullable();
            $table->unsignedInteger('event_id')->nullable();
        });
    }
}
