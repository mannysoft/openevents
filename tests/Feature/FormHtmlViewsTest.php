<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BootsWithoutDatabase;

/**
 * App\Services\FormHtml is a plain class and no alias exists, so a Blade file has to import it with
 * @use, and the import reaches only the compiled file it sits in. These views called FormHtml:: with
 * no import and threw Class "FormHtml" not found. Three also called selectRange(), which the class
 * did not have.
 *
 * Each page runs through its real route and the real exception handler. The event's club slug has
 * no row in clubs, so a priced ticket calls no Stripe.
 *
 * Most FormHtml helpers return an empty string, so these pages render with their form fields
 * missing. The tests assert what prints, not that a form can be submitted.
 */
class FormHtmlViewsTest extends TestCase
{
    use BootsWithoutDatabase;

    private const EVENT = 101;

    private const TICKET = 11;

    private const HIDDEN_TICKET = 12;

    private const ATTENDEE = 21;

    private const ORDER = 31;

    private const QUESTION = 41;

    private const USER = 51;

    private const ORDER_REFERENCE = 'PAID1';

    private const ACCESS_CODE = 'OPEN';

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
        $this->createQuestionTables();

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
        DB::table('tickets')->insert([
            ['id' => self::TICKET, 'account_id' => 1, 'event_id' => self::EVENT, 'title' => 'Fixture ticket', 'price' => 0, 'is_hidden' => 0],
            ['id' => self::HIDDEN_TICKET, 'account_id' => 1, 'event_id' => self::EVENT, 'title' => 'Hidden fixture ticket', 'price' => 0, 'is_hidden' => 1],
        ]);
        DB::table('event_access_codes')->insert(['id' => 1, 'event_id' => self::EVENT, 'code' => self::ACCESS_CODE]);
        DB::table('ticket_event_access_code')->insert(['ticket_id' => self::HIDDEN_TICKET, 'event_access_code_id' => 1]);

        DB::table('orders')->insert(['id' => self::ORDER, 'account_id' => 1, 'event_id' => self::EVENT, 'order_reference' => self::ORDER_REFERENCE, 'created_at' => '2026-10-31 10:00:00']);
        DB::table('attendees')->insert(['id' => self::ATTENDEE, 'account_id' => 1, 'order_id' => self::ORDER, 'event_id' => self::EVENT, 'ticket_id' => self::TICKET, 'first_name' => 'Fixture', 'last_name' => 'Guest', 'email' => 'guest@example.test', 'created_at' => '2026-10-31 10:00:00']);

        DB::table('question_types')->insert(['id' => config('attendize.question_radio_single'), 'name' => 'Radio']);
        DB::table('questions')->insert(['id' => self::QUESTION, 'account_id' => 1, 'title' => 'Fixture question', 'question_type_id' => config('attendize.question_radio_single'), 'is_required' => 0, 'is_enabled' => 1, 'sort_order' => 1]);
        DB::table('question_options')->insert(['question_id' => self::QUESTION, 'name' => 'Fixture option']);
        DB::table('question_answers')->insert(['account_id' => 1, 'question_id' => self::QUESTION, 'attendee_id' => self::ATTENDEE, 'event_id' => self::EVENT, 'answer_text' => 'Fixture option']);
        DB::table('event_question')->insert(['event_id' => self::EVENT, 'question_id' => self::QUESTION]);
        DB::table('question_ticket')->insert(['ticket_id' => self::TICKET, 'question_id' => self::QUESTION]);
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
        Schema::create('event_access_codes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id');
            $table->string('code');
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('ticket_event_access_code', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ticket_id');
            $table->unsignedInteger('event_access_code_id');
            $table->timestamps();
        });
        Schema::create('event_images', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id');
            $table->timestamps();
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
    }

    private function createOrderTables(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
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
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('event_id');
            $table->unsignedInteger('ticket_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->boolean('is_cancelled')->default(false);
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

    private function createQuestionTables(): void
    {
        Schema::create('question_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });
        Schema::create('questions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->string('title');
            $table->unsignedInteger('question_type_id');
            $table->boolean('is_required');
            $table->boolean('is_enabled');
            $table->integer('sort_order');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('question_options', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('question_id');
            $table->string('name');
        });
        Schema::create('question_answers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('question_id');
            $table->unsignedInteger('attendee_id');
            $table->unsignedInteger('event_id');
            $table->text('answer_text');
            $table->timestamps();
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

    /**
     * The partial loops over the ticket's enabled questions, so a ticket with none never reached
     * FormHtml. Only a radio or checkbox prints anything of its own: the option's label.
     */
    public function test_the_checkout_page_renders_a_ticket_question(): void
    {
        $this->startOrder();

        $response = $this->get('/e/'.self::EVENT.'/checkout/create');

        $response->assertOk();
        $response->assertViewIs('Public.ViewEvent.EventPageCheckout');
        $response->assertSee('Fixture option');
    }

    public function test_an_access_code_shows_the_hidden_ticket(): void
    {
        $response = $this->post('/e/'.self::EVENT.'/show_hidden', ['access_code' => self::ACCESS_CODE]);

        $response->assertOk();
        $response->assertViewIs('Public.ViewEvent.Partials.EventHiddenTicketsSelection');
        $response->assertSee('Hidden fixture ticket');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function checkoutGateways(): array
    {
        return [
            'Stripe SCA' => ['Public.ViewEvent.Partials.PaymentStripeSCA', 'id="stripe-sca-payment-form"'],
            'Dummy' => ['Public.ViewEvent.Partials.Dummy', 'form-control card-number'],
        ];
    }

    /**
     * The account's payment gateway row names the partial, so which of these runs is data.
     */
    #[DataProvider('checkoutGateways')]
    public function test_the_payment_page_renders_the_partial_of_the_gateway(string $partial, string $marker): void
    {
        DB::table('payment_gateways')->update(['checkout_blade_template' => $partial]);
        DB::table('events')->update(['nmprice' => 25]);
        DB::table('tickets')->update(['price' => 25]);

        $response = $this->get('/e/'.self::EVENT.'/checkout/payment');

        $response->assertOk();
        $response->assertViewIs('Public.ViewEvent.EventPagePayment');
        $response->assertSee($marker, false);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function adminPages(): array
    {
        $event = '/event/'.self::EVENT;
        $attendee = $event.'/attendees/'.self::ATTENDEE;
        $question = $event.'/question/'.self::QUESTION;

        return [
            'edit user' => ['/user', 'ManageUser.Modals.EditUser'],
            'select organiser' => ['/select_organisers', 'ManageOrganiser.SelectOrganiser'],
            'create ticket' => [$event.'/tickets/create', 'ManageEvent.Modals.CreateTicket'],
            'edit ticket' => [$event.'/tickets/edit/'.self::TICKET, 'ManageEvent.Modals.EditTicket'],
            'print attendees' => [$event.'/attendees/print', 'ManageEvent.PrintAttendees'],
            'edit attendee' => [$attendee.'/edit', 'ManageEvent.Modals.EditAttendee'],
            'cancel attendee' => [$attendee.'/cancel', 'ManageEvent.Modals.CancelAttendee'],
            'manage order' => ['/event/order/'.self::ORDER, 'ManageEvent.Modals.ManageOrder'],
            'edit question' => [$question, 'ManageEvent.Modals.EditQuestion'],
            'view answers' => [$question.'/answers', 'ManageEvent.Modals.ViewAnswers'],
        ];
    }

    #[DataProvider('adminPages')]
    public function test_an_admin_page_renders(string $page, string $view): void
    {
        $response = $this->actingAs(User::find(self::USER))->get($page);

        $response->assertOk();
        $response->assertViewIs($view);
    }
}
