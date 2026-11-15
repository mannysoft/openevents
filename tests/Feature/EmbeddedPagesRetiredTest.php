<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BootsWithoutDatabase;

/**
 * The embedded pages were /e/{id}/embed and the is_embedded=1 copies of the checkout and order
 * pages, built to sit in an iframe on another site. Their layout threw Class "FormHtml" not found
 * on every render from November 2024 until the import landed, and the production logs hold no such
 * line, so no request reached them in that time. With the import they render hollow: FormHtml's
 * form helpers return empty strings, so there is no Register button and there are no inputs.
 *
 * They are retired. /e/{id}/embed is a 404 and is_embedded=1 no longer changes which page renders.
 * The Widgets page existed to print the iframe code for /embed, so it goes with them.
 *
 * Each page runs through its real route and the real exception handler.
 */
class EmbeddedPagesRetiredTest extends TestCase
{
    use BootsWithoutDatabase;

    private const EVENT = 101;

    private const TICKET = 11;

    private const USER = 51;

    private const ORDER_REFERENCE = 'PAID1';

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

        DB::table('users')->insert(['id' => self::USER, 'account_id' => 1, 'first_name' => 'Fixture', 'last_name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'not-a-hash']);
        DB::table('organisers')->insert(['id' => 1, 'account_id' => 1, 'name' => 'Fixture organiser']);
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
            'start_date' => '2026-12-01 18:00:00',
            'end_date' => '2026-12-01 20:00:00',
            'event_timezone' => 'Europe/London',
            'is_live' => 1,
        ]);
        DB::table('tickets')->insert(['id' => self::TICKET, 'account_id' => 1, 'event_id' => self::EVENT, 'title' => 'Fixture ticket', 'price' => 0, 'is_hidden' => 0]);
        DB::table('orders')->insert(['event_id' => self::EVENT, 'order_reference' => self::ORDER_REFERENCE, 'created_at' => '2026-10-31 10:00:00']);
    }

    private function createEventTables(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
            $table->softDeletes();
        });
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
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('tickets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('event_id');
            $table->integer('sort_order')->default(0);
            $table->string('title');
            $table->decimal('price', 10, 2);
            $table->integer('min_per_person')->default(1);
            $table->integer('max_per_person')->default(5);
            $table->boolean('is_hidden');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('event_images', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id');
            $table->timestamps();
        });
        Schema::create('event_stats', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id');
            $table->date('date');
            $table->integer('views')->default(0);
            $table->integer('unique_views')->default(0);
        });
        Schema::create('event_access_codes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id');
            $table->string('code');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('organisers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('currencies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code');
            $table->string('title');
        });
        Schema::create('questions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('event_question', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id');
            $table->unsignedInteger('question_id');
        });
        Schema::create('question_ticket', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ticket_id');
            $table->unsignedInteger('question_id');
        });
        Schema::create('Wo_Clickfunnel_Event_visit_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email_id');
            $table->string('event_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('club')->nullable();
            $table->dateTime('created')->nullable();
            $table->date('date_created')->nullable();
            $table->boolean('is_ipaddress')->default(false);
            $table->timestamps();
        });
        Schema::create('Wo_Clickfunnel_Event_SpeakerData', function (Blueprint $table) {
            $table->increments('id');
            $table->string('event_id');
            $table->string('email_id');
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
            $table->boolean('is_payment_received')->default(true);
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
    }

    /**
     * Reserves a ticket the way the ticket form does, which puts the order in the session.
     */
    private function startOrder(): void
    {
        $this->post('/e/'.self::EVENT.'/checkout', [
            'tickets' => [self::TICKET],
            'ticket_'.self::TICKET => 1,
        ], ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJson(['status' => 'success']);
    }

    public function test_the_embed_url_is_a_404(): void
    {
        $this->get('/e/'.self::EVENT.'/embed')->assertNotFound();
    }

    /**
     * The event page takes any slug, so the refusal has to match the whole word and nothing more.
     *
     * @return array<string, array{string}>
     */
    public static function eventPageUrls(): array
    {
        return [
            'no slug' => [''],
            'the event slug' => ['/fixture-night'],
            'a slug that starts with embedded' => ['/embedded-finance-evening'],
            'a slug that starts with embed' => ['/embed-your-brand'],
        ];
    }

    #[DataProvider('eventPageUrls')]
    public function test_the_event_page_keeps_every_other_slug(string $slug): void
    {
        $response = $this->get('/e/'.self::EVENT.$slug);

        $response->assertOk();
        $response->assertViewIs('Public.ViewEvent.EventPage');
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function orderStates(): array
    {
        return [
            'paid' => [true],
            'unpaid' => [false],
        ];
    }

    #[DataProvider('orderStates')]
    public function test_the_order_page_ignores_is_embedded(bool $isPaymentReceived): void
    {
        DB::table('orders')->update(['is_payment_received' => $isPaymentReceived]);

        $response = $this->get('/order/'.self::ORDER_REFERENCE.'?is_embedded=1');

        $response->assertOk();
        $response->assertViewIs('Public.ViewEvent.EventPageViewOrder');
        $response->assertSee(self::ORDER_REFERENCE);
    }

    public function test_the_checkout_page_ignores_is_embedded(): void
    {
        $this->startOrder();

        $response = $this->get('/e/'.self::EVENT.'/checkout/create?is_embedded=1');

        $response->assertOk();
        $response->assertViewIs('Public.ViewEvent.EventPageCheckout');
        $response->assertSee('Fixture ticket');
    }

    public function test_a_lapsed_checkout_returns_to_the_event_page(): void
    {
        $response = $this->get('/e/'.self::EVENT.'/checkout/create?is_embedded=1');

        $response->assertRedirect(route('showEventPage', ['event_id' => self::EVENT]));
    }

    public function test_the_widgets_page_is_a_404(): void
    {
        $response = $this->actingAs(User::find(self::USER))->get('/event/'.self::EVENT.'/widgets');

        $response->assertNotFound();
    }

    public function test_the_event_menu_has_no_widgets_link(): void
    {
        $response = $this->actingAs(User::find(self::USER))->get('/event/'.self::EVENT.'/access_codes');

        $response->assertOk();
        $response->assertViewIs('ManageEvent.AccessCodes');
        $response->assertSee('/event/'.self::EVENT.'/surveys', false);
        $response->assertDontSee('/event/'.self::EVENT.'/widgets', false);
    }

    public function test_nothing_is_left_to_serve_them(): void
    {
        $this->assertFalse(Route::has('showEmbeddedEventPage'));
        $this->assertFalse(Route::has('showEventWidgets'));
        $this->assertFalse(method_exists(Event::class, 'getEmbedUrlAttribute'));
        $this->assertFalse(method_exists(Event::class, 'getEmbedHtmlCodeAttribute'));

        foreach ([
            'Public.ViewEvent.Embedded.EventPage',
            'Public.ViewEvent.Embedded.EventPageCheckout',
            'Public.ViewEvent.Embedded.EventPageViewOrder',
            'Public.ViewEvent.Embedded.Partials.PoweredByEmbedded',
            'Public.ViewEvent.Layouts.EmbeddedEventPage',
            'ManageEvent.Widgets',
        ] as $view) {
            $this->assertFalse(View::exists($view), "$view is still there");
        }

        // The route table and the view finder were read: the normal pages are still in them.
        $this->assertTrue(Route::has('showEventPage'));
        $this->assertTrue(View::exists('Public.ViewEvent.EventPageViewOrder'));
    }
}
