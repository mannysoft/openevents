<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BootsWithoutDatabase;

/**
 * /masterclass, /events and /clubs/{club} each read one feed, the last two from an AWS Lambda. A
 * dropped connection or an odd answer gave a 500, and a hung Lambda held a worker with no limit.
 * Like the homepage, a page now shows its empty list and logs a warning with nothing from the answer.
 */
class PublicEventPagesFeedsTest extends TestCase
{
    use BootsWithoutDatabase;

    /** Sits in every bad answer below, so the tests can prove none of it was written down. */
    private const CANARY = 'visitor@example.test';

    /** Each page's route, the name its warning gives the feed, and the feed's URL. */
    private const PAGES = [
        'masterclass' => ['/masterclass', '/api/masterclasses', '*/api/masterclasses'],
        'calendar' => ['/events', '/prod/fetchvirtualevent', '*/prod/fetchvirtualevent*'],
        'club' => ['/clubs/london', '/prod/getclubevents', '*/prod/getclubevents*'],
    ];

    private const ROWS_ON_THE_PAGE = [
        'masterclass' => ['Upcoming Class Row', 'Past Class Row'],
        'calendar' => ['Club Row', 'Webinar Row'],
        'club' => ['First Club Row', 'Second Club Row'],
    ];

    /** Every key a page needs from every row. */
    private const REQUIRED_KEYS = [
        'masterclass' => [
            'is_past', 'video_recording_url', 'masterclass_url', 'masterclass_banner',
            'start_date', 'start_time', 'title', 'description',
        ],
        'calendar' => ['postTitle', 'webinar', 'postImage', 'postDescription', 'start', 'end'],
        'club' => ['start_date', 'start_time', 'eventUrl', 'eventType', 'postImage', 'postTitle'],
    ];

    /** @var array<int, MessageLogged> */
    private array $warnings = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $this->app->forgetInstance('encrypter');
        config(['app.lounge_url' => 'https://lounge.test', 'app.url' => 'https://events.test']);

        Http::preventStrayRequests();

        Event::listen(MessageLogged::class, function (MessageLogged $message) {
            if ($message->level === 'warning') {
                $this->warnings[] = $message;
            }
        });
    }

    #[DataProvider('pages')]
    public function test_a_healthy_feed_fills_the_page_and_logs_nothing(string $page): void
    {
        $this->fakeFeed($page, Http::response(['data' => $this->rows($page)]));

        $response = $this->get(self::PAGES[$page][0]);

        $response->assertOk();
        $response->assertSee('All Rights Reserved');
        foreach (self::ROWS_ON_THE_PAGE[$page] as $row) {
            $response->assertSee($row);
        }
        $this->assertSame([], $this->warnings);
    }

    public static function pages(): array
    {
        return [
            'masterclass' => ['masterclass'],
            'calendar' => ['calendar'],
            'club' => ['club'],
        ];
    }

    #[DataProvider('pages')]
    public function test_an_empty_list_is_a_normal_answer_not_a_failure(string $page): void
    {
        $this->fakeFeed($page, Http::response(['data' => []]));

        $this->get(self::PAGES[$page][0])->assertOk();

        $this->assertSame([], $this->warnings);
    }

    #[DataProvider('oneFeedFailing')]
    public function test_a_failed_feed_gives_the_page_with_an_empty_list(string $page, string $failure, string $reason): void
    {
        $this->fakeFeed($page, $this->failure($failure, $page));

        $response = $this->get(self::PAGES[$page][0]);

        $response->assertOk();
        $response->assertSee('All Rights Reserved');
        foreach (self::ROWS_ON_THE_PAGE[$page] as $row) {
            $response->assertDontSee($row);
        }

        $this->assertCount(1, $this->warnings);
        $this->assertSame(['feed' => self::PAGES[$page][1], 'reason' => $reason], $this->warnings[0]->context);
        $this->assertStringNotContainsString(
            self::CANARY,
            $this->warnings[0]->message.json_encode($this->warnings[0]->context)
        );
    }

    public static function oneFeedFailing(): array
    {
        $failures = [
            'answers 500 with a JSON error' => 'HTTP 500',
            'answers 503 with an HTML page' => 'HTTP 503',
            'answers 404' => 'HTTP 404',
            'answers 500 around a good list' => 'HTTP 500',
            'drops the connection' => 'ConnectionException',
            'answers broken JSON' => 'malformed answer',
            'answers HTML with a 200' => 'malformed answer',
            'answers a JSON error with a 200' => 'malformed answer',
            'answers a null list' => 'malformed answer',
            'answers a list of scalars' => 'malformed answer',
            'answers an object where the list belongs' => 'malformed answer',
        ];

        $cases = [];
        foreach (array_keys(self::PAGES) as $page) {
            foreach ($failures as $failure => $reason) {
                $cases["$page $failure"] = [$page, $failure, $reason];
            }
        }

        return $cases;
    }

    #[DataProvider('requiredKeys')]
    public function test_a_row_without_a_key_the_page_needs_empties_the_list(string $page, string $key): void
    {
        $rows = array_map(function (array $row) use ($key) {
            unset($row[$key]);

            return $row;
        }, $this->rows($page));

        $this->fakeFeed($page, Http::response(['data' => $rows]));

        $response = $this->get(self::PAGES[$page][0]);

        $response->assertOk();
        foreach (self::ROWS_ON_THE_PAGE[$page] as $row) {
            $response->assertDontSee($row);
        }
        $this->assertSame(['feed' => self::PAGES[$page][1], 'reason' => 'malformed answer'], $this->warnings[0]->context ?? null);
    }

    #[DataProvider('requiredKeys')]
    public function test_an_array_where_the_page_prints_a_value_costs_only_its_row(string $page, string $key): void
    {
        $badRow = array_merge($this->rows($page)[0], [$key => ['nested' => self::CANARY]]);

        $this->fakeFeed($page, Http::response(['data' => [...$this->rows($page), $badRow]]));

        $response = $this->get(self::PAGES[$page][0]);

        $response->assertOk();
        $response->assertDontSee(self::CANARY);
        foreach (self::ROWS_ON_THE_PAGE[$page] as $row) {
            $response->assertSee($row);
        }
        $this->assertSame(
            [['feed' => self::PAGES[$page][1], 'reason' => 'value the page cannot print', 'rows' => 1]],
            array_column($this->warnings, 'context')
        );
    }

    public static function requiredKeys(): array
    {
        $cases = [];
        foreach (self::REQUIRED_KEYS as $page => $keys) {
            foreach ($keys as $key) {
                $cases["$page $key"] = [$page, $key];
            }
        }

        return $cases;
    }

    #[DataProvider('pages')]
    public function test_a_hung_feed_costs_seconds_not_a_worker(string $page): void
    {
        $seen = [];
        Http::fake(function ($request, array $options) use (&$seen) {
            $seen[] = $options;

            return Http::response(['data' => []]);
        });

        $this->get(self::PAGES[$page][0])->assertOk();

        $this->assertCount(1, $seen);
        $this->assertGreaterThan(0, $seen[0]['timeout']);
        $this->assertLessThanOrEqual(10, $seen[0]['timeout']);
        $this->assertLessThan($seen[0]['timeout'], $seen[0]['connect_timeout']);
    }

    /** The curl calls these replaced verified TLS, and the Lambdas have valid certificates. */
    #[DataProvider('lambdaPages')]
    public function test_the_lambda_calls_still_verify_tls(string $page): void
    {
        $seen = [];
        Http::fake(function ($request, array $options) use (&$seen) {
            $seen[] = $options;

            return Http::response(['data' => []]);
        });

        $this->get(self::PAGES[$page][0])->assertOk();

        $this->assertNotFalse($seen[0]['verify'] ?? true);
    }

    public static function lambdaPages(): array
    {
        return ['calendar' => ['calendar'], 'club' => ['club']];
    }

    public function test_the_calendar_asks_the_lambda_for_what_it_always_asked_for(): void
    {
        $this->fakeFeed('calendar', Http::response(['data' => []]));

        $this->get('/events')->assertOk();

        Http::assertSent(fn ($request) => $request->url()
            === 'https://cz7qklu85m.execute-api.eu-west-2.amazonaws.com/prod/fetchvirtualevent?limit=50&offset=0&userId=1');
    }

    public function test_the_calendar_builds_each_row_as_before(): void
    {
        $this->fakeFeed('calendar', Http::response(['data' => $this->rows('calendar')]));

        $calendar = $this->calendarOn($this->get('/events')->assertOk());

        $this->assertSame([
            [
                'title' => 'Club Row',
                'eventurl' => 'https://events.test/club/101',
                'image' => 'https://images.test/a.jpg',
                'description' => 'About the row',
                'start' => '2030-01-15 18:00:00',
                'end' => '2030-01-15 20:00:00',
                'className' => 'fc-bg-default',
                'icon' => 'rocket',
            ],
            [
                'title' => 'Webinar Row',
                'eventurl' => 'https://webinar.test/join',
                'image' => 'https://images.test/a.jpg',
                'description' => 'About the row',
                'start' => '2030-01-15 18:00:00',
                'end' => '2030-01-15 20:00:00',
                'className' => 'fc-bg-default',
                'icon' => 'rocket',
            ],
        ], $calendar);
    }

    public function test_the_masterclass_page_builds_each_row_as_before(): void
    {
        $this->fakeFeed('masterclass', Http::response(['data' => $this->rows('masterclass')]));

        $calendar = $this->calendarOn($this->get('/masterclass')->assertOk());

        $this->assertSame([
            [
                'title' => 'Upcoming Class Row',
                'eventurl' => 'https://lounge.test/join',
                'image' => 'https://images.test/c.jpg',
                'description' => 'About the class',
                'start' => '2030-02-01T10:00',
                'className' => 'fc-bg-default',
                'icon' => 'rocket',
            ],
            [
                'title' => 'Past Class Row',
                'eventurl' => 'https://lounge.test/recording',
                'image' => 'https://images.test/c.jpg',
                'description' => 'About the class',
                'start' => '2030-02-01T10:00',
                'className' => 'fc-bg-default',
                'icon' => 'rocket',
            ],
        ], $calendar);
    }

    /** Which of getId and eventurl a calendar row reads depends on the row. */
    #[DataProvider('calendarLinks')]
    public function test_a_calendar_row_needs_only_the_link_it_reads(array $row, bool $kept): void
    {
        $badRow = array_merge($this->calendarRow(['postTitle' => 'Extra Row']), $row);
        $badRow = array_filter($badRow, fn ($value) => $value !== 'absent');

        $this->fakeFeed('calendar', Http::response(['data' => [...$this->rows('calendar'), $badRow]]));

        $response = $this->get('/events');

        $response->assertOk();
        $this->assertSame(
            array_merge(['Club Row', 'Webinar Row'], $kept ? ['Extra Row'] : []),
            array_column($this->calendarOn($response), 'title')
        );
        $this->assertSame(
            $kept ? [] : [['feed' => '/prod/fetchvirtualevent', 'reason' => 'no usable link', 'rows' => 1]],
            array_column($this->warnings, 'context')
        );
    }

    public static function calendarLinks(): array
    {
        return [
            'a club row without eventurl stays' => [['eventurl' => 'absent'], true],
            'a club row with a blank getId stays' => [['getId' => ''], true],
            'a club row with a null getId stays' => [['getId' => null], true],
            'a club row with an array in eventurl stays' => [['eventurl' => ['nested']], true],
            'a webinar row without getId stays' => [['webinar' => '1', 'getId' => 'absent'], true],
            'a webinar row with an array in getId stays' => [['webinar' => '1', 'getId' => ['nested']], true],
            'a club row without getId goes' => [['getId' => 'absent'], false],
            'a club row with an array in getId goes' => [['getId' => ['nested']], false],
            'a webinar row without eventurl goes' => [['webinar' => '1', 'eventurl' => 'absent'], false],
            'a webinar row with an array in eventurl goes' => [['webinar' => '1', 'eventurl' => ['nested']], false],
        ];
    }

    public function test_the_club_page_says_there_are_no_events_when_the_feed_fails(): void
    {
        $this->fakeFeed('club', Http::response('', 500));

        $this->get('/clubs/london')->assertOk()->assertSee('No Upcoming Events Found');
    }

    public function test_the_online_club_still_reads_the_accra_feed(): void
    {
        $this->fakeFeed('club', Http::response(['data' => []]));

        $this->get('/clubs/online')->assertOk();

        Http::assertSent(fn ($request) => $request['clubName'] === 'accra');
    }

    public function test_a_club_name_cannot_add_parameters_to_the_feed_url(): void
    {
        $this->fakeFeed('club', Http::response(['data' => []]));

        $this->get('/clubs/london%26admin%3D1')->assertOk();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/prod/getclubevents?clubName=london%26admin%3D1'));
    }

    private function fakeFeed(string $page, $response): void
    {
        Http::fake([self::PAGES[$page][2] => $response]);
    }

    private function failure(string $failure, string $page)
    {
        return match ($failure) {
            'answers 500 with a JSON error' => Http::response(['message' => 'Server Error', 'email' => self::CANARY], 500),
            'answers 503 with an HTML page' => Http::response('<html>'.self::CANARY.'</html>', 503),
            'answers 404' => Http::response(['message' => 'Not Found'], 404),
            'answers 500 around a good list' => Http::response(['data' => $this->rows($page)], 500),
            'drops the connection' => Http::failedConnection('cURL error 28: timed out reading '.self::CANARY),
            'answers broken JSON' => Http::response('{"data": [{"title": "'.self::CANARY, 200),
            'answers HTML with a 200' => Http::response('<html>Back soon</html>', 200),
            'answers a JSON error with a 200' => Http::response(['message' => 'Server Error'], 200),
            'answers a null list' => Http::response(['data' => null]),
            'answers a list of scalars' => Http::response(['data' => [1, 2]]),
            'answers an object where the list belongs' => Http::response(['data' => ['title' => 'One Row']]),
        };
    }

    private function rows(string $page): array
    {
        return match ($page) {
            'masterclass' => [
                $this->classRow(['title' => 'Upcoming Class Row']),
                $this->classRow(['title' => 'Past Class Row', 'is_past' => true]),
            ],
            'calendar' => [
                $this->calendarRow(['postTitle' => 'Club Row']),
                $this->calendarRow(['postTitle' => 'Webinar Row', 'webinar' => '1']),
            ],
            'club' => [
                $this->clubRow(['postTitle' => 'First Club Row']),
                $this->clubRow(['postTitle' => 'Second Club Row']),
            ],
        };
    }

    /** The shape of the Lounge's MasterclassResource, cut down to what the page reads. */
    private function classRow(array $overrides): array
    {
        return array_merge([
            'start_time' => '10:00',
            'start_date' => '2030-02-01',
            'title' => 'A Row',
            'description' => 'About the class',
            'masterclass_url' => 'https://lounge.test/join',
            'masterclass_banner' => 'https://images.test/c.jpg',
            'video_recording_url' => 'https://lounge.test/recording',
            'is_past' => false,
        ], $overrides);
    }

    private function calendarRow(array $overrides): array
    {
        return array_merge([
            'postTitle' => 'A Row',
            'webinar' => '0',
            'getId' => '101',
            'eventurl' => 'https://webinar.test/join',
            'postImage' => 'https://images.test/a.jpg',
            'postDescription' => 'About the row',
            'start' => '2030-01-15 18:00:00',
            'end' => '2030-01-15 20:00:00',
        ], $overrides);
    }

    private function clubRow(array $overrides): array
    {
        return array_merge([
            'start_date' => '2030-01-15',
            'start_time' => '18:00',
            'eventUrl' => 'https://events.test/club/101',
            'eventType' => 'Networking',
            'postImage' => 'https://images.test/a.jpg',
            'postTitle' => 'A Row',
        ], $overrides);
    }

    /** What the page hands the calendar script. */
    private function calendarOn($response): array
    {
        $this->assertSame(1, preg_match('/var passedArray = (.*);/', $response->getContent(), $found));

        return json_decode($found[1], true);
    }
}
