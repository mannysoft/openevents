<?php

namespace Tests\Feature;

require_once __DIR__.'/../Support/CurlRecorder.php';

use App\Http\Controllers\API\MembersApiController;
use App\Http\Controllers\EventViewController;
use App\Models\Event;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Stevebauman\Location\Facades\Location;
use Tests\Concerns\BootsWithoutDatabase;
use Tests\Support\CurlRecorder;

/**
 * Three public routes took a code from Wo_Clickfunnel_Event_Data and created a Lobby account with
 * it. That table has taken no row since 3 March 2024, and sign-ups now go to
 * Wo_Clickfunnel_Event_Data_New, which has no code column. Nothing showed the form on the booking
 * page either. The old codes still worked, because the handlers checked neither age nor status, so
 * the routes are gone, along with the hidden form and its scripts.
 *
 * CurlRecorder stays armed for every test here, so a handler that came back would be recorded
 * instead of reaching the Lobby.
 */
class VerifyCodeHandlersRemovedTest extends TestCase
{
    use BootsWithoutDatabase;

    private const CODE_ROW_ID = 501;

    private const THE_CODE = ['verificationCode' => '123456', 'mcode' => self::CODE_ROW_ID, 'event_id_code' => 1];

    /** What the Lobby would answer to each call a handler makes on the way to a new account. */
    private const LOBBY_ANSWERS = [
        '{"api_status":200,"access_token":"fixture-lobby-token"}',
        '{"api_status":400}',
        '{"api_status":200,"access_token":"fixture-new-token","user_id":77}',
        '{}',
    ];

    private const EVENT_TITLE = 'Fixture night';

    private const REMOVED_MARKUP = [
        'id="verify"',
        'formCode',
        'id="mcode"',
        'verificationCode',
        'event_id_code',
        'reSendOtp',
        'resendotp',
        '/verifyCode',
        'myFunction2',
        'messageCode\'',
        '#messageCode\'',
    ];

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
        CurlRecorder::arm(self::LOBBY_ANSWERS);

        $this->createCodeTable();
        $this->createEventTables();
        $this->createLoungeTables();

        DB::table('organisers')->insert(['id' => 1, 'name' => 'Fixture organiser']);
        DB::table('currencies')->insert(['id' => 3, 'code' => 'GBP', 'title' => 'Pound']);
    }

    protected function tearDown(): void
    {
        CurlRecorder::disarm();

        parent::tearDown();
    }

    public static function removedRoutes(): array
    {
        return [
            'web verifyCode' => ['/verifyCode'],
            'api verifyRegisterCode' => ['/api/verifyRegisterCode'],
            'api manualVerifyCode' => ['/api/manualVerifyCode'],
        ];
    }

    #[DataProvider('removedRoutes')]
    public function test_a_code_that_still_matches_gets_404_and_reaches_nobody(string $uri): void
    {
        $this->postJson($uri, self::THE_CODE)->assertNotFound();

        $this->assertSame([], CurlRecorder::$requests);
        $this->assertNull(DB::table('Wo_Clickfunnel_Event_Data')->value('status'));
    }

    public function test_the_route_table_holds_none_of_them(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())->map(fn ($route) => $route->uri());

        foreach (['verifyCode', 'api/verifyRegisterCode', 'api/manualVerifyCode'] as $uri) {
            $this->assertNotContains($uri, $uris);
        }

        // The table was read, and the routes beside them are still in it.
        $this->assertContains('postBuyNow', $uris);
        $this->assertContains('saveSpeakerData', $uris);
        $this->assertContains('api/saveFreeUser', $uris);
        $this->assertContains('api/updateSpeakerPrice', $uris);
    }

    public function test_the_handlers_are_gone_with_their_routes(): void
    {
        $this->assertFalse(method_exists(EventViewController::class, 'verifyCodeEvent'));
        $this->assertFalse(method_exists(MembersApiController::class, 'verifyCodeEventApi'));
        $this->assertFalse(method_exists(MembersApiController::class, 'manualVerifyCode'));
    }

    /**
     * Every page that includes TicketBookSection, with two exceptions. The member page has its own
     * test. Losangeles.blade.php cannot render with or without this change: it includes two
     * partials that 48fa0be4 deleted.
     *
     * @return array<string, array{string, string, array<string, int>}>
     */
    public static function clubPages(): array
    {
        return [
            'default template' => ['alpha', 'Public.ClubEvents.Default.Default', []],
            'default template, can book right' => ['alpha', 'Public.ClubEvents.Default.Default', ['can_book_right' => 1]],
            'summit template' => ['alpha', 'Public.ClubEvents.Default.Summit', ['is_summit_template' => 1]],
            'Cyprus' => ['cyprus', 'Public.ClubEvents.Cyprus.Cyprus', []],
            'London Mayfair' => ['london-mayfair', 'Public.ClubEvents.LondonMayFair.LondonMayFair', []],
            'New York' => ['newyork', 'Public.ClubEvents.Newyork.Newyork', []],
            'Orange County' => ['orangecounty', 'Public.ClubEvents.Orangecounty.Orangecounty', []],
            'Paris' => ['paris', 'Public.ClubEvents.Paris.Paris', []],
        ];
    }

    /**
     * @param  array<string, int>  $columns
     */
    #[DataProvider('clubPages')]
    public function test_a_club_page_keeps_its_booking_section(string $club, string $view, array $columns): void
    {
        $event = $this->createEvent($club, $columns);

        $response = $this->get("/club/$event");

        $response->assertOk()->assertViewIs($view)->assertSee(self::EVENT_TITLE);
        $this->assertBookingSectionIsIntact((string) $response->getContent());
    }

    /**
     * The member route takes the event from a Lambda through a bare Guzzle client, so this renders
     * the view directly, with an Event in place of the Lambda's reply. The route does not pass
     * canBookRight or is_allowed_attend_event, which the partial reads. That is a separate bug, so
     * this passes them.
     */
    public function test_the_member_page_keeps_its_booking_section(): void
    {
        session()->start();
        $event = Event::find($this->createEvent('alpha', ['is_member' => 1]));
        $event->postTitle = self::EVENT_TITLE;

        $html = view('Public.MemberEvents.Member.Default', [
            'event' => $event,
            'email' => 'test@gmail.com',
            'avatar' => '',
            'club' => '',
            'eventsVideo' => '',
            'extraDescription' => '',
            'loginData' => '0',
            'member' => '0',
            'firstname' => '',
            'lastname' => '',
            'oldEvent' => 0,
            'phone_number' => '',
            'isMemberEvent' => true,
            'canBookRight' => false,
            'is_allowed_attend_event' => true,
        ])->render();

        $this->assertBookingSectionIsIntact($html);
    }

    private function assertBookingSectionIsIntact(string $html): void
    {
        foreach (self::REMOVED_MARKUP as $removed) {
            $this->assertStringNotContainsString($removed, $html);
        }
        $this->assertStringEndsWith('</html>', rtrim($html));

        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        // #before-login is hidden for a signed-in visitor, so the tickets must not end up inside it.
        $this->assertSame(1, $xpath->query('//*[@id="before-login"]/following-sibling::*[@id="ticket-grid"]')->length);

        foreach (['firstname', 'lastname', 'email', 'telephone', 'submitrEGISTER'] as $id) {
            $this->assertSame(1, $xpath->query("//*[@id=\"before-login\"]//*[@id=\"$id\"]")->length, $id);
        }

        // The form held one of the page's id="token" inputs, and the scripts read the first there is.
        $token = $xpath->query('//input[@id="token"]')->item(0);
        $this->assertNotNull($token);
        $this->assertSame(session()->token(), $token->getAttribute('value'));
    }

    /**
     * @param  array<string, int>  $columns
     */
    private function createEvent(string $club, array $columns): int
    {
        DB::connection('mysql_lounge')->table('clubs')->insert([
            'slug' => $club,
            'is_default_template' => $club === 'alpha' ? 1 : 0,
        ]);

        return DB::table('events')->insertGetId($columns + [
            'account_id' => 1,
            'organiser_id' => 1,
            'currency_id' => 3,
            'title' => self::EVENT_TITLE,
            'description' => 'Fixture description',
            'club' => $club,
            'event_ttype' => 'Online',
            'start_date' => '2026-12-01 18:00:00',
            'end_date' => '2026-12-01 20:00:00',
            'event_timezone' => 'Europe/London',
            'is_live' => 1,
        ]);
    }

    private function createCodeTable(): void
    {
        Schema::create('Wo_Clickfunnel_Event_Data', function (Blueprint $table) {
            $table->increments('id');
            foreach (['code', 'event_id', 'password', 'email_id', 'full_name', 'mobile_no', 'mautic_id', 'status'] as $column) {
                $table->string($column)->nullable();
            }
        });

        DB::table('Wo_Clickfunnel_Event_Data')->insert([
            'id' => self::CODE_ROW_ID, 'code' => '123456', 'event_id' => '1', 'password' => 'fixture-password',
            'email_id' => 'fixture.person@example.test', 'full_name' => 'Fixture Person', 'mobile_no' => '+447700900123',
            'mautic_id' => '801',
        ]);
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
            $table->string('event_ttype');
            $table->decimal('nmprice', 10, 2)->default(0);
            $table->boolean('can_book_right')->default(false);
            $table->boolean('is_summit_template')->default(false);
            $table->boolean('is_member')->default(false);
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
            $table->string('title')->default('Fixture ticket');
            $table->decimal('price', 10, 2)->default(0);
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
        Schema::create('Wo_Clickfunnel_Event_Purchase', function (Blueprint $table) {
            $table->increments('id');
            $table->string('event_id');
            $table->string('email_id');
            $table->string('zoom_url')->nullable();
            $table->string('amount')->nullable();
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
            // The club pages read these from the row with no guard.
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
}
