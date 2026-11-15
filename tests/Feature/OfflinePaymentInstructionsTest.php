<?php

namespace Tests\Feature;

use App\Events\OrderCompletedEvent;
use App\Http\Controllers\EventCheckoutController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BootsWithoutDatabase;

/**
 * Two public partials print an event's offline payment instructions through the Markdown facade:
 * the order page for an unpaid order, and the payment page when the event takes offline payment.
 * Neither partial imported the facade and no alias exists, so both threw Class "Markdown" not found.
 *
 * The instructions column is nullable and convertToHtml() takes a string. An event whose order page
 * settings were never saved holds null there, and that threw too, so the partials cast it.
 *
 * completeOrder() read the choice to pay offline from a session key nothing writes, so such an order
 * came out complete and paid with no money taken. It now comes out awaiting payment.
 *
 * Each page runs through its real route and the real exception handler, signed out. The event's
 * club slug has no row in clubs, so a priced ticket takes the platform card form and calls no Stripe.
 */
class OfflinePaymentInstructionsTest extends TestCase
{
    use BootsWithoutDatabase;

    private const EVENT = 101;

    private const ORDER_PAGE = '/order/UNPAID1';

    private const PAYMENT_PAGE = '/e/101/checkout/payment';

    private const CHECKOUT = '/e/101/checkout/create';

    // is_member() answers for this address without calling the Lounge.
    private const BUYER = 'test@gmail.com';

    private const INSTRUCTIONS = '**Bank transfer** to sort code 12-34-56';

    private const INSTRUCTIONS_HTML = '<strong>Bank transfer</strong> to sort code 12-34-56';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $this->app->forgetInstance('encrypter');

        $sqlite = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        config([
            'app.debug' => false,
            'database.connections.events_test' => $sqlite,
            'database.connections.mysql_lounge' => $sqlite,
            'database.default' => 'events_test',
        ]);
        DB::purge('events_test');
        DB::purge('mysql_lounge');

        Http::preventStrayRequests();

        $this->createEventTables();
        $this->createOrderTables();
        $this->createPaymentTables();

        Schema::connection('mysql_lounge')->create('clubs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug');
            $table->string('meta_pixel')->nullable();
            $table->boolean('is_default_template')->default(false);
        });

        DB::table('organisers')->insert(['id' => 1, 'name' => 'Fixture organiser']);
        DB::table('currencies')->insert(['id' => 3, 'code' => 'GBP', 'title' => 'Pound']);
        DB::table('payment_gateways')->insert(['id' => 1, 'checkout_blade_template' => 'Public.ViewEvent.Partials.PaymentStripe']);
        DB::table('accounts')->insert(['id' => 1, 'payment_gateway_id' => 1]);
        DB::table('account_payment_gateways')->insert(['id' => 1, 'account_id' => 1, 'payment_gateway_id' => 1, 'config' => '{"publishableKey":"pk_test_fixture"}']);

        DB::table('events')->insert([
            'id' => self::EVENT,
            'account_id' => 1,
            'organiser_id' => 1,
            'currency_id' => 3,
            'title' => 'Fixture night',
            'description' => 'Fixture description',
            'club' => 'no-such-club',
            'nmprice' => 25,
            'start_date' => '2026-12-01 18:00:00',
            'end_date' => '2026-12-01 20:00:00',
            'event_timezone' => 'Europe/London',
            'is_live' => 1,
            'enable_offline_payments' => 1,
            'offline_payment_instructions' => self::INSTRUCTIONS,
        ]);
        DB::table('tickets')->insert(['id' => 1, 'event_id' => self::EVENT, 'title' => 'Fixture ticket', 'price' => 25]);
        DB::table('orders')->insert(['event_id' => self::EVENT, 'order_reference' => 'UNPAID1', 'created_at' => '2026-10-31 10:00:00']);
    }

    private function createEventTables(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('organiser_id');
            $table->unsignedInteger('currency_id');
            $table->string('title');
            $table->text('description');
            $table->string('club')->nullable();
            $table->decimal('nmprice', 10, 2)->default(0);
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('event_timezone');
            $table->boolean('is_live');
            $table->boolean('enable_offline_payments')->default(false);
            $table->text('offline_payment_instructions')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('tickets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id');
            $table->integer('sort_order')->default(0);
            $table->string('title');
            $table->decimal('price', 10, 2);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('event_images', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id');
            $table->timestamps();
        });
        Schema::create('organisers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('currencies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code');
            $table->string('title');
        });
    }

    private function createOrderTables(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id');
            $table->string('order_reference');
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('organiser_booking_fee', 10, 2)->default(0);
            // As in the migration: a row that does not set this column reads as unpaid.
            $table->boolean('is_payment_received')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
        });
        Schema::create('attendees', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function createPaymentTables(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('payment_gateway_id');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('account_payment_gateways', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('payment_gateway_id');
            $table->text('config');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->increments('id');
            $table->string('checkout_blade_template');
        });
        Schema::create('reserved_tickets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ticket_id');
            $table->unsignedInteger('event_id');
            $table->integer('quantity_reserved');
            $table->dateTime('expires');
            $table->string('session_id');
            $table->timestamps();
        });
        Schema::create('Wo_Clickfunnel_Event_book_log', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->dateTime('created');
            $table->string('club')->nullable();
            $table->string('event_id');
        });
    }

    /**
     * The tables completeOrder() writes, and the calls it makes out: the Lobby and Zoom saves curl
     * real endpoints, and the OrderCompletedEvent listener mails the tickets. Signed in, so
     * postCreateOrder() does not go on to create a Lobby user.
     */
    private function prepareCheckout(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['first_name', 'last_name', 'email', 'contact_no', 'transaction_id'] as $column) {
                $table->string($column)->nullable();
            }
            foreach (['order_status_id', 'account_id', 'payment_gateway_id', 'points_deducted'] as $column) {
                $table->unsignedInteger($column)->nullable();
            }
            foreach (['booking_fee', 'discount', 'taxamt', 'club_commission'] as $column) {
                $table->decimal($column, 10, 2)->nullable();
            }
            $table->boolean('is_member')->nullable();
            $table->boolean('is_paid_points')->nullable();
        });
        Schema::table('tickets', function (Blueprint $table) {
            foreach (['quantity_sold', 'sales_volume', 'organiser_fees_volume', 'booking_fee', 'organiser_booking_fee'] as $column) {
                $table->decimal($column, 10, 2)->default(0);
            }
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('title')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('unit_booking_fee', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::table('attendees', function (Blueprint $table) {
            foreach (['first_name', 'last_name', 'email'] as $column) {
                $table->string($column)->nullable();
            }
            foreach (['event_id', 'ticket_id', 'account_id', 'reference_index', 'private_reference_number'] as $column) {
                $table->unsignedInteger($column)->nullable();
            }
        });
        Schema::create('event_stats', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id');
            $table->date('date')->nullable();
            foreach (['tickets_sold', 'sales_volume', 'organiser_fees_volume'] as $column) {
                $table->decimal($column, 10, 2)->default(0);
            }
            $table->timestamps();
        });
        Schema::create('questions', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('question_ticket', function (Blueprint $table) {
            $table->unsignedInteger('question_id');
            $table->unsignedInteger('ticket_id');
        });

        Event::fake([OrderCompletedEvent::class]);
        $this->partialMock(EventCheckoutController::class, function (MockInterface $mock) {
            $mock->shouldReceive('saveUserLobbyEvent', 'saveUserZoomEvent')->andReturn('test');
        });
        $this->withSession(['access_token' => 'fixture']);
    }

    private function payOffline(): TestResponse
    {
        return $this->post(self::CHECKOUT, [
            'pay_offline' => 1,
            'firstname' => 'Fixture',
            'lastname' => 'Buyer',
            'email' => self::BUYER,
            'phone' => '0',
        ]);
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function emptyInstructions(): array
    {
        return [
            'never saved' => [null],
            'saved empty' => [''],
        ];
    }

    public function test_an_unpaid_order_shows_the_instructions_as_html_and_a_paid_order_does_not(): void
    {
        $unpaid = $this->get(self::ORDER_PAGE);

        $unpaid->assertOk();
        $unpaid->assertSee(self::INSTRUCTIONS_HTML, false);
        $unpaid->assertSee('</html>', false);

        DB::table('orders')->update(['is_payment_received' => 1]);

        $paid = $this->get(self::ORDER_PAGE);

        $paid->assertOk();
        $paid->assertDontSee(self::INSTRUCTIONS_HTML, false);
        $paid->assertDontSee('offline_payment_instructions well', false);
    }

    #[DataProvider('emptyInstructions')]
    public function test_an_unpaid_order_renders_when_the_event_has_no_instructions(?string $instructions): void
    {
        DB::table('events')->update(['offline_payment_instructions' => $instructions]);

        $response = $this->get(self::ORDER_PAGE);

        $response->assertOk();
        $response->assertSee('offline_payment_instructions well', false);
        $response->assertSee('</html>', false);
    }

    public function test_offline_payment_is_offered_only_for_a_priced_ticket_on_an_event_taking_it(): void
    {
        $offered = $this->get(self::PAYMENT_PAGE);

        $offered->assertOk();
        $offered->assertSee('name="pay_offline"', false);
        $offered->assertSee(self::INSTRUCTIONS_HTML, false);
        $offered->assertSee('</html>', false);

        DB::table('events')->update(['enable_offline_payments' => 0]);

        $eventNotTakingIt = $this->get(self::PAYMENT_PAGE);

        $eventNotTakingIt->assertOk();
        $eventNotTakingIt->assertDontSee('pay_offline', false);
        $eventNotTakingIt->assertDontSee(self::INSTRUCTIONS_HTML, false);

        DB::table('events')->update(['enable_offline_payments' => 1, 'nmprice' => 0]);
        DB::table('tickets')->update(['price' => 0]);

        $freeTicket = $this->get(self::PAYMENT_PAGE);

        $freeTicket->assertOk();
        $freeTicket->assertDontSee('pay_offline', false);
        $freeTicket->assertDontSee(self::INSTRUCTIONS_HTML, false);
    }

    #[DataProvider('emptyInstructions')]
    public function test_a_priced_ticket_renders_when_the_event_has_no_instructions(?string $instructions): void
    {
        DB::table('events')->update(['offline_payment_instructions' => $instructions]);

        $response = $this->get(self::PAYMENT_PAGE);

        $response->assertOk();
        $response->assertSee('name="pay_offline"', false);
        $response->assertSee('</html>', false);
    }

    /**
     * The page's own offline form cannot send this yet: it has no CSRF token and no buyer fields,
     * so a real click gets a 419. This is the POST made by hand, the one way to reach the branch.
     */
    public function test_paying_offline_leaves_an_order_unpaid_only_for_a_priced_ticket_on_an_event_taking_it(): void
    {
        $this->prepareCheckout();

        $response = $this->payOffline();

        $response->assertOk();
        $response->assertJson(['status' => 'success']);
        $order = DB::table('orders')->where('email', self::BUYER)->sole();
        $this->assertSame(0, (int) $order->is_payment_received);
        $this->assertSame(config('attendize.order.awaiting_payment'), (int) $order->order_status_id);
        $this->assertNull($order->payment_gateway_id);

        $orderPage = $this->get(parse_url($response->json('redirectUrl'), PHP_URL_PATH));

        $orderPage->assertOk();
        $orderPage->assertSee(self::INSTRUCTIONS_HTML, false);

        DB::table('events')->update(['enable_offline_payments' => 0]);

        $this->payOffline();

        $this->assertSame(1, DB::table('orders')->where('email', self::BUYER)->count());

        DB::table('events')->update(['enable_offline_payments' => 1, 'nmprice' => 0]);
        DB::table('tickets')->update(['price' => 0]);

        $this->payOffline()->assertOk();

        $freeOrder = DB::table('orders')->where('email', self::BUYER)->orderByDesc('id')->first();
        $this->assertNotSame($order->id, $freeOrder->id);
        $this->assertSame(1, (int) $freeOrder->is_payment_received);
        $this->assertSame(config('attendize.order.complete'), (int) $freeOrder->order_status_id);
    }
}
