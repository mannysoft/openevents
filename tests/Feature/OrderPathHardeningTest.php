<?php

namespace Tests\Feature;

use App\Gateways\EventGateway;
use App\Http\Controllers\EventCheckoutController;
use App\Services\PointsRedemptionGuard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BootsWithoutDatabase;

/**
 * The follow-up to #350, from an independent review of it.
 *
 * Paying with points: postCreateOrder took `use_points` and `equivalet_points` from the request,
 * with the balance check commented out, and completeOrder then wrote to the Lobby's database
 * (a `points` row, and the balance on the Wo_Users row matching the typed email) outside the
 * order's transaction. #350 let anonymous visitors past registration, so it is now refused
 * unless PointsRedemptionGuard allows it.
 *
 * Nothing here leaves the machine. The Lobby's database is an in-memory SQLite connection, and
 * the Lounge's address points at a closed local port, so a stray points look-up fails at once.
 */
class OrderPathHardeningTest extends TestCase
{
    use BootsWithoutDatabase;

    private const EVENT_ID = 3201;

    private const MEMBER_EMAIL = 'fixture.member@example.test';

    private const OTHER_EMAIL = 'fixture.other@example.test';

    private const LOBBY_REFERENCE = 'fixture-lobby-reference';

    private const LOBBY_USER_ID = 77;

    private const MEMBER_POINTS = 500;

    /** @var array<int, MessageLogged> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $this->app->forgetInstance('encrypter');

        $memory = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        config([
            'database.connections.events_test' => $memory,
            'database.connections.mysql_lobby' => $memory,
            'database.default' => 'events_test',
            'app.lounge_url' => 'http://127.0.0.1:9',
        ]);
        DB::purge('events_test');
        DB::purge('mysql_lobby');

        $this->createTables();

        Event::listen(MessageLogged::class, function (MessageLogged $message) {
            $this->logged[] = $message;
        });
    }

    #[DataProvider('sessions')]
    public function test_who_may_pay_with_points(bool $switchedOn, array $session, string $orderEmail, bool $allowed): void
    {
        config(['services.lobby_points.redemption' => $switchedOn]);
        session($session);

        $this->assertSame($allowed, PointsRedemptionGuard::allows($orderEmail));
    }

    public static function sessions(): array
    {
        $signedInAsMember = ['access_token' => self::LOBBY_REFERENCE, 'events_email' => self::MEMBER_EMAIL, 'email' => self::MEMBER_EMAIL];

        return [
            'switched off, even for her own sign-in' => [false, $signedInAsMember, self::MEMBER_EMAIL, false],
            'not signed in' => [true, [], self::MEMBER_EMAIL, false],
            'a guest the Lobby knew (emailExists)' => [true, ['access_token' => 'emailExists', 'email' => self::MEMBER_EMAIL, 'events_email' => self::MEMBER_EMAIL], self::MEMBER_EMAIL, false],
            'a guest without a Lobby account' => [true, ['access_token' => EventGateway::NO_LOBBY_ACCOUNT, 'email' => self::MEMBER_EMAIL], self::MEMBER_EMAIL, false],
            'signed in, with only a typed email' => [true, ['access_token' => self::LOBBY_REFERENCE, 'email' => self::MEMBER_EMAIL], self::MEMBER_EMAIL, false],
            'signed in as someone else' => [true, ['access_token' => self::LOBBY_REFERENCE, 'events_email' => self::OTHER_EMAIL], self::MEMBER_EMAIL, false],
            'signed in as her, no order email' => [true, $signedInAsMember, '', false],
            'signed in as her, her order' => [true, ['access_token' => self::LOBBY_REFERENCE, 'events_email' => ' Fixture.Member@Example.test '], self::MEMBER_EMAIL, true],
        ];
    }

    /**
     * The review's case: no session at all, use_points=1 and a large equivalet_points.
     */
    public function test_an_anonymous_points_payment_is_refused_before_anything_runs(): void
    {
        $response = $this->post(route('postCreateOrder', ['event_id' => self::EVENT_ID]), [
            'use_points' => 1,
            'equivalet_points' => 99999,
            'email' => self::MEMBER_EMAIL,
            'firstname' => 'Fixturefirst',
            'lastname' => 'Fixturelast',
            'phone' => '+447700900123',
        ]);

        $response->assertRedirect(route('showEventPayment', ['event_id' => self::EVENT_ID, 'is_payment_failed' => 1]));
        $response->assertSessionHas('message');
        $this->assertLobbyUntouched();
        $this->assertNoBuyerDetailsLogged();

        $warnings = array_values(array_filter($this->logged, fn (MessageLogged $m) => $m->level === 'warning'));
        $this->assertCount(1, $warnings);
        $this->assertSame('Points payment refused', $warnings[0]->message);
    }

    /**
     * Whatever put it in the session, an order marked as paid with points is not completed, so
     * neither the free order nor the Lobby write happens. Zero and negative amounts included:
     * zero made a free order, a negative one raised her balance.
     */
    #[DataProvider('pointsAmounts')]
    public function test_an_order_marked_as_paid_with_points_is_not_completed(int $points): void
    {
        session(['ticket_order_'.self::EVENT_ID => [
            'event_id' => self::EVENT_ID,
            'order_email' => self::MEMBER_EMAIL,
            'order_requires_payment' => false,
            'is_paid_points' => true,
            'points_deducted' => $points,
        ]]);

        $response = $this->app->make(EventCheckoutController::class)->completeOrder(self::EVENT_ID, true);

        $this->assertSame('error', $response->getData(true)['status']);
        $this->assertLobbyUntouched();
        $this->assertNoBuyerDetailsLogged();

        $errors = array_values(array_filter($this->logged, fn (MessageLogged $m) => $m->level === 'error'));
        $this->assertCount(1, $errors);
        $this->assertSame('Order not completed; rolled back', $errors[0]->message);
        $this->assertSame('RuntimeException', $errors[0]->context['error']);
        $this->assertSame(self::EVENT_ID, $errors[0]->context['event_id']);
    }

    public static function pointsAmounts(): array
    {
        return ['the review\'s large amount' => [99999], 'zero' => [0], 'negative' => [-500]];
    }

    /**
     * The four log lines the review named. The rollback line is exercised above; the other three
     * sit behind Stripe calls, so their source is checked.
     */
    public function test_the_order_path_logs_ids_not_buyer_details(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/EventCheckoutController.php'));

        $this->assertDoesNotMatchRegularExpression('/Log::\w+\([^;]*\$stripeEmail/', $source);
        $this->assertStringNotContainsString('json_encode($ghlContact)', $source);
        $this->assertStringContainsString("Log::info('Already purchased: no ticket order in the session', ['event_id' => \$event_id]);", $source);
        $this->assertStringContainsString("Log::info('Already purchased: a succeeded charge exists for this buyer and event', ['event_id' => \$event_id]);", $source);
    }

    /**
     * The terms box and the name, email and phone fields are `required`. preventDefault() (#350)
     * also skipped the browser's check of them, so the page now asks for it.
     */
    public function test_the_pay_button_asks_the_browser_to_check_the_form_first(): void
    {
        $view = file_get_contents(resource_path('views/Public/ViewEvent/Partials/new_stripe.blade.php'));

        $prevent = strpos($view, '.preventDefault();');
        $check = strpos($view, "document.getElementById('stripe-payment-form').reportValidity()");
        $confirm = strpos($view, 'stripe.confirmPayment(');

        $this->assertNotFalse($check, 'reportValidity() is not called on the payment form');
        $this->assertLessThan($check, $prevent);
        $this->assertLessThan($confirm, $check);
    }

    public function test_the_receipt_carries_the_registered_office(): void
    {
        $receipt = file_get_contents(resource_path('views/receipt.blade.php'));

        $this->assertStringContainsString('Brentano Suite, Centennial Avenue, Elstree,', $receipt);
        $this->assertStringContainsString('WD6 3SY', $receipt);
        $this->assertStringNotContainsString('AL5 4US', $receipt);
        $this->assertStringNotContainsString('Harpenden', $receipt);
    }

    private function assertLobbyUntouched(): void
    {
        $this->assertSame(self::MEMBER_POINTS, (int) DB::connection('mysql_lobby')->table('Wo_Users')
            ->where('user_id', self::LOBBY_USER_ID)->value('points'));
        $this->assertSame(0, DB::connection('mysql_lobby')->table('points')->count());
    }

    private function assertNoBuyerDetailsLogged(): void
    {
        foreach ($this->logged as $message) {
            $this->assertStringNotContainsString(self::MEMBER_EMAIL, $message->message.json_encode($message->context));
        }
    }

    private function createTables(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('club')->nullable();
            $table->date('event_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('events')->insert(['id' => self::EVENT_ID, 'title' => 'Fixture evening', 'club' => 'london']);

        Schema::connection('mysql_lobby')->create('Wo_Users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->string('email')->nullable();
            $table->integer('points')->default(0);
        });

        Schema::connection('mysql_lobby')->create('points', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('points')->nullable();
            $table->string('description')->nullable();
            $table->integer('total_points')->nullable();
            $table->timestamps();
        });

        DB::connection('mysql_lobby')->table('Wo_Users')->insert([
            'user_id' => self::LOBBY_USER_ID,
            'email' => self::MEMBER_EMAIL,
            'points' => self::MEMBER_POINTS,
        ]);
    }
}
