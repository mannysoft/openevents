<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Stevebauman\Location\Facades\Location;
use Tests\Concerns\BootsWithoutDatabase;

/**
 * Both public event layouts end with the Meta pixel partial, Public.ClubEvents.fbpixels, which
 * passes $event->club to get_pixel_id(). That takes a string, so an event with no club threw a
 * TypeError at the very end of the page, and the visitor got the 500 page instead of the event.
 *
 * An event with no club now renders with no pixel. Everything else keeps the pixel it had.
 *
 * Each page runs through its real route and the real exception handler, signed out.
 */
class EventPagePixelTest extends TestCase
{
    use BootsWithoutDatabase;

    private const NO_CLUB = 101;

    private const ALPHA = 202;

    private const CLUB_WITHOUT_PIXEL = 303;

    private const SLUG_WITH_NO_CLUB = 404;

    private const EMPTY_CLUB = 505;

    private const ALPHA_PIXEL = '111222333';

    private const DEFAULT_PIXEL = '1347934389196200';

    private const EVENT_TITLE = 'Fixture night';

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
        Location::shouldReceive('get')->andReturn(false);

        $this->createEventTables();
        $this->createOrderTables();
        $this->createPaymentTables();
        $this->createLoungeTables();

        DB::connection('mysql_lounge')->table('clubs')->insert([
            ['id' => 1, 'slug' => 'alpha', 'meta_pixel' => self::ALPHA_PIXEL, 'is_default_template' => 1],
            ['id' => 2, 'slug' => 'beta', 'meta_pixel' => null, 'is_default_template' => 1],
        ]);

        DB::table('organisers')->insert(['id' => 1, 'name' => 'Fixture organiser']);
        DB::table('currencies')->insert(['id' => 3, 'code' => 'GBP', 'title' => 'Pound']);
        DB::table('payment_gateways')->insert(['id' => 1, 'checkout_blade_template' => 'Public.ViewEvent.Partials.PaymentStripe']);
        DB::table('accounts')->insert(['id' => 1, 'payment_gateway_id' => 1]);
        DB::table('account_payment_gateways')->insert(['id' => 1, 'account_id' => 1, 'payment_gateway_id' => 1, 'config' => '{"publishableKey":"pk_test_fixture"}']);

        foreach ([
            self::NO_CLUB => null,
            self::ALPHA => 'alpha',
            self::CLUB_WITHOUT_PIXEL => 'beta',
            self::SLUG_WITH_NO_CLUB => 'gamma',
            self::EMPTY_CLUB => '',
        ] as $id => $club) {
            DB::table('events')->insert([
                'id' => $id,
                'account_id' => 1,
                'organiser_id' => 1,
                'currency_id' => 3,
                'title' => self::EVENT_TITLE,
                'description' => 'Fixture description',
                'club' => $club,
                'start_date' => '2026-12-01 18:00:00',
                'end_date' => '2026-12-01 20:00:00',
                'event_timezone' => 'Europe/London',
                'is_live' => 1,
            ]);
            DB::table('tickets')->insert(['id' => $id, 'event_id' => $id, 'title' => 'Fixture ticket', 'price' => 0]);
            DB::table('orders')->insert(['event_id' => $id, 'order_reference' => "REF$id", 'created_at' => '2026-10-31 10:00:00']);
        }
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
        Schema::create('event_stats', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id');
            $table->date('date');
            $table->integer('views')->default(0);
            $table->integer('unique_views')->default(0);
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

    private function createLoungeTables(): void
    {
        $lounge = Schema::connection('mysql_lounge');

        $lounge->create('clubs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug');
            $table->string('meta_pixel')->nullable();
            $table->string('video_url')->nullable();
            $table->boolean('is_default_template')->default(false);
            // The club page reads these from the row with no guard.
            foreach ([
                'about_event', 'about_regional_director', 'after_ticket_image', 'banner_section_1',
                'banner_section_2', 'banner_section_3', 'benefits', 'book_call_image',
                'club_info_section_first_text', 'club_info_section_second_text', 'club_info_section_video',
                'description', 'director_image', 'event_video', 'global_woman_partnership',
                'global_woman_partnership_image', 'globalman_exists_text', 'meeting_section',
                'meeting_section_image_url', 'message_mirela', 'message_mirela_image', 'quote_text',
                'remember_join', 'remember_join_image', 'showup', 'showup_image', 'who_event',
                'who_event_image_url',
            ] as $column) {
                $table->text($column)->nullable();
            }
        });
        $lounge->create('club_images', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('club_id');
            $table->boolean('is_gallery_section')->default(true);
        });
        $lounge->create('event_sections', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id')->nullable();
            $table->text('section_content')->nullable();
            $table->string('section_image')->nullable();
            $table->boolean('is_placeholder')->default(false);
            $table->timestamps();
        });
    }

    /**
     * One page per route, with the fbq() calls a club event makes there. The two layouts are the
     * only places the pixel partial is included.
     *
     * @return array<string, array{string, list<string>}>
     */
    public static function pagesAndPixelCalls(): array
    {
        $pageView = ['init '.self::ALPHA_PIXEL, 'track PageView'];

        return [
            'event page, EventPage layout' => ['/e/%d', $pageView],
            'club page, ClubPage layout' => ['/club/%d', $pageView],
            'payment page, EventPage layout' => ['/e/%d/checkout/payment', $pageView],
            'order page, EventPage layout' => ['/order/REF%d', ['init '.self::ALPHA_PIXEL, 'track Purchase', 'track CompleteRegistration']],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function pages(): array
    {
        return array_map(fn (array $page) => [$page[0]], self::pagesAndPixelCalls());
    }

    #[DataProvider('pages')]
    public function test_an_event_with_no_club_renders_its_page_with_no_pixel(string $page): void
    {
        $response = $this->get(sprintf($page, self::NO_CLUB));

        $response->assertOk();
        $response->assertSee(self::EVENT_TITLE);
        $response->assertSee('</html>', false);
        $response->assertDontSee('fbq(', false);
        $response->assertDontSee('connect.facebook.net', false);
        $response->assertDontSee('facebook.com/tr?id=', false);
    }

    /**
     * With no club there is no Stripe account to route to, so a priced ticket takes the payment
     * page's other branch and the card form on the platform key.
     */
    public function test_a_paid_event_with_no_club_reaches_the_card_form_with_no_pixel(): void
    {
        DB::table('events')->where('id', self::NO_CLUB)->update(['nmprice' => 25]);
        DB::table('tickets')->where('id', self::NO_CLUB)->update(['price' => 25]);

        $response = $this->get('/e/'.self::NO_CLUB.'/checkout/payment');

        $response->assertOk();
        $response->assertSee('id="stripe-payment-form"', false);
        $response->assertSee("Stripe('pk_test_fixture')", false);
        $response->assertDontSee('fbq(', false);
    }

    /**
     * @param  list<string>  $pixelCalls
     */
    #[DataProvider('pagesAndPixelCalls')]
    public function test_an_event_with_a_club_keeps_the_pixel_of_that_club(string $page, array $pixelCalls): void
    {
        $response = $this->get(sprintf($page, self::ALPHA));

        $response->assertOk();
        $response->assertSee('connect.facebook.net/en_US/fbevents.js', false);
        $response->assertSee('facebook.com/tr?id='.self::ALPHA_PIXEL.'&ev=PageView', false);
        $this->assertSame($pixelCalls, $this->pixelCalls($response));
    }

    /**
     * @return list<string>
     */
    private function pixelCalls(TestResponse $response): array
    {
        preg_match_all("/fbq\('(\w+)', '(\w+)'/", (string) $response->getContent(), $calls, PREG_SET_ORDER);

        return array_map(fn (array $call) => "$call[1] $call[2]", $calls);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function eventsOnTheDefaultPixel(): array
    {
        return [
            'club with no pixel of its own' => [self::CLUB_WITHOUT_PIXEL],
            'slug no club has' => [self::SLUG_WITH_NO_CLUB],
            'empty club' => [self::EMPTY_CLUB],
        ];
    }

    #[DataProvider('eventsOnTheDefaultPixel')]
    public function test_an_event_with_a_club_value_but_no_pixel_stays_on_the_default_pixel(int $event): void
    {
        $response = $this->get('/e/'.$event);

        $response->assertOk();
        $response->assertSee("fbq('init', '".self::DEFAULT_PIXEL."');", false);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function eventsWithNoClubName(): array
    {
        return [
            'no club' => [self::NO_CLUB],
            'empty club' => [self::EMPTY_CLUB],
        ];
    }

    #[DataProvider('eventsWithNoClubName')]
    public function test_a_club_page_with_no_club_name_takes_its_title_from_the_event(int $event): void
    {
        $response = $this->get('/club/'.$event);

        $response->assertOk();
        $response->assertSee('<title>'.self::EVENT_TITLE.' - Global Woman Events</title>', false);
        $response->assertSee('<meta property="og:title" content="'.self::EVENT_TITLE.' - Global Woman Events" />', false);
    }

    public function test_a_club_page_keeps_the_club_name_as_its_title(): void
    {
        $response = $this->get('/club/'.self::ALPHA);

        $response->assertOk();
        $response->assertSee('<title>Alpha - Global Woman Events</title>', false);
        $response->assertSee('<meta property="og:title" content="Alpha - Global Woman Events" />', false);
    }
}
