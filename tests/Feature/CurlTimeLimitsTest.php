<?php

namespace Tests\Feature;

require_once __DIR__.'/../Support/CurlRecorder.php';

use App\Events\OrderCompletedEvent;
use App\Http\Controllers\EventCheckoutController;
use App\Http\Controllers\EventViewController;
use App\Http\Controllers\UserLoginController;
use App\Models\Ticket;
use ErrorException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BootsWithoutDatabase;
use Tests\Support\CurlRecorder;

/**
 * A hung upstream used to hold a PHP-FPM worker until FPM killed the request at 30 s. On the order
 * path that kill lands inside an open transaction, which rolls back an order the buyer has paid
 * for. Each call here now has a time limit, and each test pins what happens when that limit is
 * hit. No request leaves the machine: CurlRecorder answers curl_exec(), and a false answer is what
 * cURL returns on a timeout.
 */
class CurlTimeLimitsTest extends TestCase
{
    use BootsWithoutDatabase;

    private const MOST_SECONDS = 10;

    /** Lower for the order path: it runs per attendee, with ticket rows held. */
    private const ORDER_PATH_MOST_SECONDS = 5;

    private const FPM_KILLS_AT_SECONDS = 30;

    private const LARGE_ORDER = 8;

    private const BUYER_EMAIL = 'fixture.buyer@example.test';

    private const BUYER_FIRST_NAME = 'Fixturefirst';

    private const SPEAKER_ROW_ID = 601;

    private const LOBBY_EVENT_ID = 701;

    /** @var array<int, MessageLogged> */
    private array $warnings = [];

    private int $ordersCompleted = 0;

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

        Event::listen(MessageLogged::class, function (MessageLogged $message) {
            if ($message->level === 'warning') {
                $this->warnings[] = $message;
            }
        });
    }

    protected function tearDown(): void
    {
        CurlRecorder::disarm();
        Carbon::setTestNow();
        unset($_GET['code']);

        parent::tearDown();
    }

    public function test_the_mobile_number_call_is_limited(): void
    {
        CurlRecorder::arm(['{"status":200}']);

        $response = $this->postJson('/postBuyMobileUpdate', ['telNumber' => '+447700900123', 'lobbyIdUser' => '9']);

        $response->assertOk()->assertExactJson(['status' => 'success']);
        $this->assertSame('+447700900123', session('phone'));
        $this->assertCount(1, CurlRecorder::$requests);
        $this->assertTimeLimited(CurlRecorder::$requests[0]);
        $this->assertSame([], $this->warnings);
    }

    /**
     * The form shows "Please Try after some time" on fail, and nothing at all on a 500.
     */
    #[DataProvider('mobileNumberFailures')]
    public function test_a_mobile_number_call_that_fails_reads_as_fail(string|false $answer, int $warnings): void
    {
        CurlRecorder::arm([$answer]);

        $response = $this->postJson('/postBuyMobileUpdate', ['telNumber' => '+447700900123', 'lobbyIdUser' => '9']);

        $response->assertOk()->assertExactJson(['status' => 'fail']);
        $this->assertNull(session('phone'));
        $this->assertCount($warnings, $this->warnings);
    }

    public static function mobileNumberFailures(): array
    {
        return [
            'timed out' => [false, 1],
            'not json' => ['<html>Bad Gateway</html>', 0],
            'no status' => ['{}', 0],
            'refused' => ['{"status":500}', 0],
        ];
    }

    public function test_the_mobile_number_warning_names_the_call_and_nothing_about_the_visitor(): void
    {
        CurlRecorder::arm([false]);

        $this->postJson('/postBuyMobileUpdate', ['telNumber' => '+447700900123', 'lobbyIdUser' => self::BUYER_EMAIL]);

        $this->assertCount(1, $this->warnings);
        $this->assertSame(['call', 'curl_errno'], array_keys($this->warnings[0]->context));
        $this->assertSame('mobilenumber', $this->warnings[0]->context['call']);
        $this->assertLogHoldsNothingAbout(['447700900123', self::BUYER_EMAIL]);
    }

    public function test_the_lobby_event_call_is_limited_for_each_attendee(): void
    {
        $this->createOrderTables();
        CurlRecorder::arm(['ok', 'ok']);

        $this->completeAnOrder(attendees: 2);

        $this->assertCount(2, CurlRecorder::$requests);
        $this->assertTimeLimited(CurlRecorder::$requests[0], self::ORDER_PATH_MOST_SECONDS);
        $this->assertTimeLimited(CurlRecorder::$requests[1], self::ORDER_PATH_MOST_SECONDS);
        $this->assertSame([], $this->warnings);
    }

    /**
     * The payment is recorded by the rows below, which commit after the Lambda call. Its reply was
     * never read, so a timeout must leave the buyer with their order.
     */
    #[DataProvider('lobbyEventCallFailures')]
    public function test_a_paid_order_commits_when_the_lobby_event_call_fails(array $answers, int $warnings): void
    {
        $this->createOrderTables();
        CurlRecorder::arm($answers);

        $response = $this->completeAnOrder(attendees: 2);

        $this->assertSame('success', $response->getData(true)['status']);
        $this->assertSame(0, DB::transactionLevel());
        $this->assertSame('ch_fixture', DB::table('orders')->value('transaction_id'));
        $this->assertSame(2, DB::table('attendees')->count());
        $this->assertSame(2, DB::table('Wo_Clickfunnel_Event_Purchase')->count());
        $this->assertEquals(2, DB::table('tickets')->value('quantity_sold'));
        $this->assertSame(1, $this->ordersCompleted);
        $this->assertCount(2, CurlRecorder::$requests);
        $this->assertCount($warnings, $this->warnings);
    }

    public static function lobbyEventCallFailures(): array
    {
        return [
            'both time out' => [[false, false], 2],
            'the first times out' => [[false, 'ok'], 1],
            'the second times out' => [['ok', false], 1],
        ];
    }

    public function test_the_lobby_event_warning_says_which_event_and_nothing_about_the_buyer(): void
    {
        $this->createOrderTables();
        CurlRecorder::arm([false]);

        $this->completeAnOrder(attendees: 1);

        $this->assertCount(1, $this->warnings);
        $this->assertSame('curl call failed', $this->warnings[0]->message);
        $this->assertSame(['call', 'lobby_event_id', 'curl_errno'], array_keys($this->warnings[0]->context));
        $this->assertSame('saveuserevent', $this->warnings[0]->context['call']);
        $this->assertEquals(self::LOBBY_EVENT_ID, $this->warnings[0]->context['lobby_event_id']);
        $this->assertLogHoldsNothingAbout([self::BUYER_EMAIL, self::BUYER_FIRST_NAME]);
    }

    /**
     * FPM kills a request at 30 s, and inside this transaction that would roll back a paid order.
     * A limit per call doesn't prevent that: six hung calls at 5 s each reach 30 s. So one order
     * stops calling once it has spent its budget, and every attendee still gets their place.
     */
    #[DataProvider('lobbyEventCallCosts')]
    public function test_one_order_stops_calling_once_its_budget_is_spent(string|false $answer, int $millisecondsPerCall, int $callsMade): void
    {
        $this->createOrderTables();
        CurlRecorder::arm(array_fill(0, self::LARGE_ORDER, $answer));

        // The clock moves on at every read, and each call reads it either side of curl_exec().
        $clock = Carbon::parse('2030-01-01 12:00:00');
        Carbon::setTestNow(function () use (&$clock, $millisecondsPerCall) {
            return $clock = $clock->copy()->addMilliseconds($millisecondsPerCall);
        });

        $response = $this->completeAnOrder(attendees: self::LARGE_ORDER);

        $this->assertSame('success', $response->getData(true)['status']);
        $this->assertSame(0, DB::transactionLevel());
        $this->assertSame(self::LARGE_ORDER, DB::table('attendees')->count());
        $this->assertSame(self::LARGE_ORDER, DB::table('Wo_Clickfunnel_Event_Purchase')->count());
        $this->assertCount($callsMade, CurlRecorder::$requests);
        $this->assertLessThan(self::FPM_KILLS_AT_SECONDS, $callsMade * $millisecondsPerCall / 1000);

        $skipped = array_filter($this->warnings, fn (MessageLogged $warning) => $warning->message === 'curl call skipped');
        $this->assertCount(self::LARGE_ORDER - $callsMade, $skipped);
        foreach ($skipped as $warning) {
            $this->assertSame(['call', 'lobby_event_id'], array_keys($warning->context));
        }
        $this->assertCount(($answer === false ? $callsMade : 0) + count($skipped), $this->warnings);
        $this->assertLogHoldsNothingAbout([self::BUYER_EMAIL, self::BUYER_FIRST_NAME]);
    }

    public static function lobbyEventCallCosts(): array
    {
        return [
            'quick calls all run' => ['ok', 1000, self::LARGE_ORDER],
            'slow calls stop at the budget' => ['ok', 3000, 4],
            'the worst case, just inside the limit' => ['ok', 4900, 3],
            'hung calls stop at the budget' => [false, 5000, 2],
        ];
    }

    public function test_the_speaker_link_call_is_limited(): void
    {
        $this->createSpeakerTables();
        CurlRecorder::arm(['{"ok":true}']);

        $this->approveTheSpeaker()->assertOk();

        $this->assertCount(1, CurlRecorder::$requests);
        $this->assertTimeLimited(CurlRecorder::$requests[0]);
        $this->assertSame([], $this->warnings);
    }

    /**
     * The speaker's approval and ticket are saved first, the Lounge ignores this reply, and its
     * speaker page has a resend button. So a timeout keeps what is saved and leaves a trace.
     */
    public function test_a_timed_out_speaker_link_keeps_the_approval_and_the_ticket(): void
    {
        $this->createSpeakerTables();
        CurlRecorder::arm([false]);

        $response = $this->approveTheSpeaker();

        $response->assertOk()->assertJsonPath('result.status', 200)->assertJsonPath('result.refId', self::SPEAKER_ROW_ID);
        $this->assertSame('approved', DB::table('Wo_Clickfunnel_Event_SpeakerData')->value('status'));
        $this->assertSame(1, DB::table('tickets')->where('title', 'Speaker Ticket')->count());
        $this->assertCount(1, $this->warnings);
        $this->assertSame(['call', 'ref_id', 'curl_errno'], array_keys($this->warnings[0]->context));
        $this->assertSame(self::SPEAKER_ROW_ID, $this->warnings[0]->context['ref_id']);
        $this->assertLogHoldsNothingAbout([self::BUYER_EMAIL]);
    }

    /**
     * EventViewController signs in from its constructor, so any page it serves reaches these two
     * calls with ?code=anything on the URL.
     */
    public function test_the_page_sign_in_calls_are_limited(): void
    {
        $_GET['code'] = 'fixture-code';
        CurlRecorder::arm([
            '{"access_token":"fixture-token","reference":"fixture-reference"}',
            json_encode(['user_data' => array_fill_keys(
                ['ref_id', 'first_name', 'avatar', 'last_name', 'username', 'email', 'phone', 'director', 'isprotype'],
                'fixture'
            )]),
        ]);

        new EventViewController;

        $this->assertCount(2, CurlRecorder::$requests);
        $this->assertTimeLimited(CurlRecorder::$requests[0]);
        $this->assertTimeLimited(CurlRecorder::$requests[1]);
        $this->assertSame('fixture-reference', session('access_token'));
        $this->assertSame([], $this->warnings);
    }

    public function test_a_timed_out_page_sign_in_leaves_the_visitor_signed_out(): void
    {
        $_GET['code'] = 'fixture-code';
        CurlRecorder::arm([false]);

        new EventViewController;

        $this->assertCount(1, CurlRecorder::$requests);
        $this->assertNull(session('access_token'));
        $this->assertSingleOauthWarningWithoutTheUrl();
    }

    public function test_a_timed_out_user_lookup_on_the_page_signs_nobody_in(): void
    {
        $_GET['code'] = 'fixture-code';
        CurlRecorder::arm(['{"access_token":"fixture-token","reference":"fixture-reference"}', false]);

        $caught = null;
        try {
            new EventViewController;
        } catch (ErrorException $exception) {
            $caught = $exception;
        }

        $this->assertNotNull($caught);
        $this->assertCount(2, CurlRecorder::$requests);
        $this->assertNull(session('access_token'));
        $this->assertSingleOauthWarningWithoutTheUrl();
    }

    public function test_the_login_callback_call_is_limited(): void
    {
        CurlRecorder::arm(['{}']);

        (new UserLoginController)->callback(Request::create('/', 'GET', ['code' => 'fixture-code']));

        $this->assertCount(1, CurlRecorder::$requests);
        $this->assertTimeLimited(CurlRecorder::$requests[0]);
        $this->assertSame([], $this->warnings);
    }

    public function test_a_timed_out_login_callback_signs_nobody_in(): void
    {
        CurlRecorder::arm([false]);

        (new UserLoginController)->callback(Request::create('/', 'GET', ['code' => 'fixture-code']));

        $this->assertCount(1, CurlRecorder::$requests);
        $this->assertNull(session('ref_id'));
        $this->assertSingleOauthWarningWithoutTheUrl();
    }

    /**
     * Bounds rather than exact numbers: the limits are judgements, and timing the services may move
     * them. The outer bound is what keeps a limit short.
     *
     * @param  array{options: array<int, mixed>}  $request
     */
    private function assertTimeLimited(array $request, int $mostSeconds = self::MOST_SECONDS): void
    {
        $connect = $request['options'][CURLOPT_CONNECTTIMEOUT] ?? 0;
        $total = $request['options'][CURLOPT_TIMEOUT] ?? 0;

        $this->assertGreaterThan(0, $connect, 'the call has no connect limit');
        $this->assertGreaterThan($connect, $total, 'the call has no total limit above its connect limit');
        $this->assertLessThanOrEqual($mostSeconds, $total);
    }

    private function assertSingleOauthWarningWithoutTheUrl(): void
    {
        $this->assertCount(1, $this->warnings);
        $this->assertSame(['call', 'curl_errno'], array_keys($this->warnings[0]->context));
        $this->assertSame('lobby-oauth', $this->warnings[0]->context['call']);
        $this->assertLogHoldsNothingAbout(['fixture-code', 'app_secret', 'authorize', 'access_token']);
    }

    /**
     * @param  list<string>  $values
     */
    private function assertLogHoldsNothingAbout(array $values): void
    {
        foreach ($this->warnings as $warning) {
            $line = $warning->message.json_encode($warning->context);

            foreach ($values as $value) {
                $this->assertFalse(str_contains($line, $value), 'a warning holds something it must not');
            }
        }
    }

    private function completeAnOrder(int $attendees): \Illuminate\Http\JsonResponse
    {
        DB::table('accounts')->insert(['id' => 1]);
        DB::table('organisers')->insert(['id' => 1, 'charge_tax' => 0]);
        DB::table('events')->insert([
            'id' => 1, 'account_id' => 1, 'organiser_id' => 1, 'title' => 'Fixture event', 'club' => 'london',
            'event_ttype' => 'Online', 'lobby_event_id' => self::LOBBY_EVENT_ID, 'start_date' => '2030-01-01 18:00:00',
        ]);
        DB::table('tickets')->insert(['id' => 1, 'event_id' => 1, 'title' => 'Online Networking Ticket', 'price' => 20]);

        Event::forget(OrderCompletedEvent::class);
        Event::listen(OrderCompletedEvent::class, function () {
            $this->ordersCompleted++;
        });

        // The shape EventGateway::bookTicket() leaves in the session for a paid booking.
        session(['ticket_order_1' => [
            'source' => 'events',
            'event_id' => 1,
            'order_first_name' => self::BUYER_FIRST_NAME,
            'order_last_name' => 'Fixturelast',
            'order_email' => self::BUYER_EMAIL,
            'contact_no' => '+447700900123',
            'ref_id' => 9,
            'tickets' => [['ticket' => Ticket::find(1), 'qty' => $attendees]],
            'total_ticket_quantity' => $attendees,
            'order_total' => 20 * $attendees,
            'booking_fee' => 0,
            'organiser_booking_fee' => 0,
            'total_booking_fee' => 0,
            'order_requires_payment' => true,
            'payment_gateway' => (object) ['id' => 1],
            'transaction_id' => ['ch_fixture'],
            'affiliate_referral' => null,
        ]]);

        return $this->app->make(EventCheckoutController::class)->completeOrder(1);
    }

    private function createOrderTables(): void
    {
        $tables = [
            'accounts' => [],
            'organisers' => ['charge_tax', 'tax_value'],
            'events' => ['account_id', 'organiser_id', 'title', 'club', 'event_ttype', 'lobby_event_id', 'start_date'],
            'orders' => [
                'event_id', 'account_id', 'club_commission', 'is_paid_points', 'points_deducted', 'transaction_id',
                'payment_gateway_id', 'first_name', 'last_name', 'contact_no', 'email', 'order_status_id', 'amount',
                'booking_fee', 'organiser_booking_fee', 'discount', 'is_payment_received', 'taxamt', 'is_member',
                'order_reference',
            ],
            'order_items' => ['order_id', 'title', 'quantity', 'unit_price', 'unit_booking_fee'],
            'attendees' => [
                'order_id', 'event_id', 'ticket_id', 'account_id', 'first_name', 'last_name', 'email',
                'reference_index', 'private_reference_number',
            ],
            'event_stats' => ['event_id', 'date'],
            'questions' => [],
            'question_ticket' => ['question_id', 'ticket_id'],
            'reserved_tickets' => ['session_id'],
            'Wo_Clickfunnel_ZoomEvent_Data' => ['event_id'],
            'Wo_Clickfunnel_Event_Purchase' => [
                'email_id', 'type', 'full_name', 'first_name', 'last_name', 'mobile_no', 'user_id', 'amount', 'event_id',
                'is_paid_points', 'points_deducted', 'created', 'is_member', 'referal_email', 'referal_first_name',
                'referal_last_name',
            ],
        ];

        foreach ($tables as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->increments('id');
                foreach ($columns as $column) {
                    $table->string($column)->nullable();
                }
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::create('tickets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id');
            $table->string('title');
            $table->decimal('price');
            foreach (['quantity_sold', 'sales_volume', 'organiser_fees_volume'] as $column) {
                $table->decimal($column)->default(0);
            }
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('event_stats', function (Blueprint $table) {
            foreach (['tickets_sold', 'sales_volume', 'organiser_fees_volume'] as $column) {
                $table->decimal($column)->default(0);
            }
        });
    }

    private function createSpeakerTables(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->increments('id');
            foreach ([
                'event_id', 'title', 'quantity_available', 'start_sale_date', 'end_sale_date', 'price', 'min_per_person',
                'max_per_person', 'description', 'account_id', 'user_id', 'is_hidden', 'created_at', 'updated_at',
            ] as $column) {
                $table->string($column)->nullable();
            }
        });

        Schema::create('Wo_Clickfunnel_Event_SpeakerData', function (Blueprint $table) {
            $table->increments('id');
            foreach (['event_id', 'user_id', 'first_name', 'email_id', 'amount', 'status', 'approve_date'] as $column) {
                $table->string($column)->nullable();
            }
        });

        DB::table('Wo_Clickfunnel_Event_SpeakerData')->insert([
            'id' => self::SPEAKER_ROW_ID, 'event_id' => '1', 'user_id' => '9',
            'first_name' => 'Fixture', 'email_id' => self::BUYER_EMAIL,
        ]);
    }

    private function approveTheSpeaker(): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/updateSpeakerPrice', ['speakerPrice' => 25, 'eventId' => 1, 'lobbyUserId' => 9]);
    }
}
