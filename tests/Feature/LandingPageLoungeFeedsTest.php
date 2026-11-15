<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BootsWithoutDatabase;

/**
 * The homepage reads three Lounge feeds on every load. On 30-31 Oct 2026 one of them answered 500
 * (gwclub/globalwomanlounge#1052) and the whole site showed its error page for about 11.5 hours.
 * A feed that fails, hangs or answers nonsense must now cost its own section and nothing else.
 */
class LandingPageLoungeFeedsTest extends TestCase
{
    use BootsWithoutDatabase;

    /** Sits in every bad answer below, so the log test can prove none of it was written down. */
    private const CANARY = 'visitor@example.test';

    private const FEEDS = ['events', 'masterclasses', 'globalmanevents'];

    /** What each feed puts on the page: every row a section can show. */
    private const ROWS_ON_THE_PAGE = [
        'events' => ['Club Meeting Row', 'Webinar Row', 'Member Event Row'],
        'masterclasses' => ['Upcoming Class Row', 'Past Class Row'],
        'globalmanevents' => ['Upcoming Man Row', 'Past Man Row'],
    ];

    /** The page around the sections, which no feed may take down. */
    private const PAGE_FURNITURE = [
        'Global Woman Club Events',
        'Club Meetings',
        'Upcoming Global Man Events',
        'Upcoming Masterclass',
        'Past Masterclass',
        'Member Events',
        'Testimonials',
        'All Rights Reserved',
    ];

    /** Every array key the homepage views read from a row, per feed. */
    private const KEYS_THE_VIEWS_READ = [
        'events' => [
            'isMember', 'getId', 'webinar', 'timezone', 'start', 'start_date', 'start_time',
            'eventurl', 'postId', 'postImage', 'postTitle', 'event_ttype',
        ],
        'masterclasses' => [
            'is_past', 'video_recording_url', 'masterclass_url', 'masterclass_banner',
            'start_date', 'start_time', 'title',
        ],
        'globalmanevents' => [
            'is_past', 'video_recording_url', 'event_url', 'event_banner',
            'start_date', 'start_time', 'title',
        ],
    ];

    /** @var array<int, MessageLogged> */
    private array $warnings = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $this->app->forgetInstance('encrypter');
        config(['app.lounge_url' => 'https://lounge.test']);

        Http::preventStrayRequests();

        Event::listen(MessageLogged::class, function (MessageLogged $message) {
            if ($message->level === 'warning') {
                $this->warnings[] = $message;
            }
        });
    }

    public function test_healthy_feeds_fill_every_section_and_log_nothing(): void
    {
        $this->fakeFeeds();

        $response = $this->get('/');

        $response->assertOk();
        $this->assertPageFurniture($response);
        foreach (self::FEEDS as $feed) {
            $this->assertRowsShown($response, $feed);
        }
        $this->assertSame([], $this->warnings);
    }

    public function test_an_empty_list_is_a_normal_answer_not_a_failure(): void
    {
        Http::fake([
            '*/api/events' => Http::response([]),
            '*/api/masterclasses' => Http::response(['data' => []]),
            '*/api/globalmanevents' => Http::response(['data' => []]),
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $this->assertPageFurniture($response);
        $this->assertSame([], $this->warnings);
    }

    #[DataProvider('oneFeedFailing')]
    public function test_a_failed_feed_empties_only_its_own_section(string $feed, string $failure, string $reason): void
    {
        $this->fakeFeeds([$feed => $this->failure($failure, $feed)]);

        $response = $this->get('/');

        $response->assertOk();
        $this->assertPageFurniture($response);
        $this->assertRowsHidden($response, $feed);
        foreach (array_diff(self::FEEDS, [$feed]) as $healthy) {
            $this->assertRowsShown($response, $healthy);
        }

        $this->assertCount(1, $this->warnings);
        $this->assertSame(['feed' => '/api/'.$feed, 'reason' => $reason], $this->warnings[0]->context);
    }

    public static function oneFeedFailing(): array
    {
        $failures = [
            'answers 500 with a JSON error' => 'HTTP 500',
            'answers 503 with an HTML page' => 'HTTP 503',
            'answers 404' => 'HTTP 404',
            'answers 500 around a good list' => 'HTTP 500',
            'times out' => 'ConnectionException',
            'answers broken JSON' => 'malformed answer',
            'answers HTML with a 200' => 'malformed answer',
            'answers a JSON error with a 200' => 'malformed answer',
            'answers a list of scalars' => 'malformed answer',
            'answers an object where the list belongs' => 'malformed answer',
        ];

        $cases = [];
        foreach (self::FEEDS as $feed) {
            foreach ($failures as $failure => $reason) {
                $cases["$feed $failure"] = [$feed, $failure, $reason];
            }
        }

        return $cases;
    }

    #[DataProvider('keysTheViewsRead')]
    public function test_a_row_without_a_key_the_view_reads_empties_that_section(string $feed, string $key): void
    {
        $rows = array_map(function (array $row) use ($key) {
            unset($row[$key]);

            return $row;
        }, $this->rows($feed));

        $this->fakeFeeds([$feed => Http::response($this->wrapped($feed, $rows))]);

        $response = $this->get('/');

        $response->assertOk();
        $this->assertRowsHidden($response, $feed);
        foreach (array_diff(self::FEEDS, [$feed]) as $healthy) {
            $this->assertRowsShown($response, $healthy);
        }
        $this->assertSame(['feed' => '/api/'.$feed, 'reason' => 'malformed answer'], $this->warnings[0]->context ?? null);
    }

    public static function keysTheViewsRead(): array
    {
        $cases = [];
        foreach (self::KEYS_THE_VIEWS_READ as $feed => $keys) {
            foreach ($keys as $key) {
                $cases["$feed $key"] = [$feed, $key];
            }
        }

        return $cases;
    }

    #[DataProvider('keysTheViewsRead')]
    public function test_an_array_the_view_would_print_costs_only_its_row(string $feed, string $key): void
    {
        $this->fakeFeeds([$feed => $this->withExtraRow($feed, [$key => ['nested' => self::CANARY]])]);

        $response = $this->get('/');

        $response->assertOk();
        $this->assertPageFurniture($response);
        foreach (self::FEEDS as $each) {
            $this->assertRowsShown($response, $each);
        }
        $this->assertWarnings([['feed' => '/api/'.$feed, 'reason' => 'value the page cannot print', 'rows' => 1]]);
    }

    public function test_an_array_under_a_key_no_view_reads_keeps_the_row(): void
    {
        $this->fakeFeeds(['events' => $this->withExtraRow('events', ['postTitle' => 'Extra Row', 'avatarArray' => ['a']])]);

        $this->get('/')->assertOk()->assertSee('Extra Row');
        $this->assertSame([], $this->warnings);
    }

    #[DataProvider('unreadableDates')]
    public function test_an_unreadable_date_costs_only_its_row(array $badValues): void
    {
        $this->fakeFeeds(['events' => $this->withExtraRow('events', ['postTitle' => 'Extra Row'] + $badValues)]);

        $response = $this->get('/');

        $response->assertOk();
        $this->assertPageFurniture($response);
        $response->assertDontSee('Extra Row');
        foreach (self::FEEDS as $feed) {
            $this->assertRowsShown($response, $feed);
        }
        $this->assertWarnings([['feed' => '/api/events', 'reason' => 'unreadable date', 'rows' => 1]]);
    }

    public static function unreadableDates(): array
    {
        return [
            'start, club row' => [['start' => '6pm till late']],
            'start_date, club row' => [['start_date' => 'next week sometime']],
            'start, webinar row' => [['start' => '6pm till late', 'webinar' => '1']],
            'start, member row' => [['start' => '6pm till late', 'isMember' => '1']],
            'both, club row' => [['start' => 'tbc', 'start_date' => 'tbc']],
        ];
    }

    /** DateTime reads a blank date as now, so the view never threw on one. */
    #[DataProvider('blankDates')]
    public function test_a_blank_date_is_not_an_unreadable_one(array $blanks): void
    {
        $this->fakeFeeds(['events' => $this->withExtraRow('events', ['postTitle' => 'Extra Row'] + $blanks)]);

        $this->get('/')->assertOk()->assertSee('Extra Row');
        $this->assertSame([], $this->warnings);
    }

    public static function blankDates(): array
    {
        return [
            'null start' => [['start' => null]],
            'empty start' => [['start' => '']],
            'null start_date' => [['start_date' => null]],
            'empty start_date' => [['start_date' => '']],
        ];
    }

    #[DataProvider('unreadableTimezones')]
    public function test_an_unreadable_timezone_reads_as_blank_and_keeps_the_row(array $values): void
    {
        $this->fakeFeeds(['events' => $this->withExtraRow('events', ['postTitle' => 'Extra Row'] + $values)]);

        $response = $this->get('/');

        $response->assertOk();
        $this->assertPageFurniture($response);
        $response->assertSee('Extra Row');
        foreach (self::FEEDS as $feed) {
            $this->assertRowsShown($response, $feed);
        }
        $this->assertWarnings([['feed' => '/api/events', 'reason' => 'unreadable timezone', 'rows' => 1]]);
    }

    public static function unreadableTimezones(): array
    {
        return [
            'club row' => [['timezone' => 'GMT+1 London']],
            'webinar row' => [['timezone' => 'GMT+1 London', 'webinar' => '1']],
            'member row' => [['timezone' => 'GMT+1 London', 'isMember' => '1']],
            // DateTimeZone throws a ValueError here, which is not an Exception.
            'NUL byte' => [['timezone' => "Europe/London\0"]],
            'number' => [['timezone' => 12345]],
        ];
    }

    #[DataProvider('blankTimezones')]
    public function test_a_webinar_with_a_blank_timezone_counts_in_the_fallback_like_the_other_loops(mixed $blank): void
    {
        $this->fakeFeeds(['events' => Http::response([
            $this->eventRow([
                'postTitle' => 'Webinar Row',
                'webinar' => '1',
                'timezone' => $blank,
                'start' => '2030-01-17 18:00:00',
            ]),
        ])]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Webinar Row');
        $response->assertSee('data-countdown="2030/01/17 18:00:00"', false);
        $this->assertSame([], $this->warnings);
    }

    public static function blankTimezones(): array
    {
        return ['null' => [null], 'empty string' => ['']];
    }

    public function test_good_rows_count_down_to_the_same_moments_as_before(): void
    {
        $this->fakeFeeds(['events' => Http::response([
            $this->eventRow(['start_date' => '2030-01-15', 'start' => '2030-01-15 18:00:00']),
            $this->eventRow(['start_date' => '2030-01-16', 'start' => '2030-01-16 18:00:00', 'timezone' => null]),
            $this->eventRow(['start' => '2030-01-17 18:00:00', 'webinar' => '1']),
        ])]);

        $response = $this->get('/')->assertOk();

        foreach ([
            '2030/01/15 05:30:00', // Top list: midnight in London, in Kolkata.
            '2030/01/15 23:30:00', // Club Meetings: 18:00 in London, in Kolkata.
            '2030/01/16 00:00:00', // Top list, blank timezone.
            '2030/01/16 18:00:00', // Club Meetings, blank timezone.
            '2030/01/17 23:30:00', // Webinars.
        ] as $moment) {
            $response->assertSee('data-countdown="'.$moment.'"', false);
        }
        $this->assertSame([], $this->warnings);
    }

    public function test_several_bad_rows_are_counted_in_one_warning_each(): void
    {
        $this->fakeFeeds(['events' => Http::response([
            ...$this->rows('events'),
            $this->eventRow(['start' => 'tbc']),
            $this->eventRow(['start_date' => 'tbc']),
            $this->eventRow(['timezone' => 'GMT+1 London']),
            $this->eventRow(['timezone' => 'GMT+2 Paris']),
            $this->eventRow(['timezone' => 'GMT+3 Athens']),
            $this->eventRow(['postImage' => ['nested']]),
        ])]);

        $this->get('/')->assertOk();

        $this->assertWarnings([
            ['feed' => '/api/events', 'reason' => 'value the page cannot print', 'rows' => 1],
            ['feed' => '/api/events', 'reason' => 'unreadable date', 'rows' => 2],
            ['feed' => '/api/events', 'reason' => 'unreadable timezone', 'rows' => 3],
        ]);
    }

    #[DataProvider('badValuesCarryingTheCanary')]
    public function test_a_row_warning_records_nothing_from_the_row(array $badValues): void
    {
        $this->fakeFeeds(['events' => $this->withExtraRow('events', $badValues)]);

        $this->get('/')->assertOk();

        $this->assertCount(1, $this->warnings);
        $this->assertSame(['feed', 'reason', 'rows'], array_keys($this->warnings[0]->context));
        $this->assertStringNotContainsString(
            self::CANARY,
            $this->warnings[0]->message.json_encode($this->warnings[0]->context)
        );
    }

    public static function badValuesCarryingTheCanary(): array
    {
        return [
            'timezone' => [['timezone' => self::CANARY]],
            'start' => [['start' => self::CANARY]],
            'start_date' => [['start_date' => self::CANARY]],
            'an array' => [['postTitle' => ['nested' => self::CANARY]]],
        ];
    }

    public function test_the_page_survives_every_feed_failing_at_once(): void
    {
        Http::fake([
            '*/api/events' => $this->failure('answers 500 with a JSON error', 'events'),
            '*/api/masterclasses' => $this->failure('times out', 'masterclasses'),
            '*/api/globalmanevents' => $this->failure('answers broken JSON', 'globalmanevents'),
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $this->assertPageFurniture($response);
        foreach (self::FEEDS as $feed) {
            $this->assertRowsHidden($response, $feed);
        }
        $this->assertSame(
            ['/api/events', '/api/masterclasses', '/api/globalmanevents'],
            array_map(fn (MessageLogged $warning) => $warning->context['feed'], $this->warnings)
        );
    }

    public function test_a_hung_lounge_costs_seconds_not_minutes(): void
    {
        $seen = [];
        Http::fake(function ($request, array $options) use (&$seen) {
            $seen[] = $options;

            return str_ends_with($request->url(), '/api/events')
                ? Http::response([])
                : Http::response(['data' => []]);
        });

        $this->get('/')->assertOk();

        // The calls run one after another, so a hung Lounge costs their sum. Laravel's default is
        // 30 seconds each.
        $this->assertCount(3, $seen);
        $this->assertLessThan(30, array_sum(array_column($seen, 'timeout')));
        foreach ($seen as $options) {
            $this->assertLessThan($options['timeout'], $options['connect_timeout']);
        }
    }

    #[DataProvider('failuresCarryingTheCanary')]
    public function test_the_warning_records_nothing_from_the_answer(string $failure): void
    {
        $this->fakeFeeds(['masterclasses' => $this->failure($failure, 'masterclasses')]);

        $this->get('/')->assertOk();

        $this->assertCount(1, $this->warnings);
        $this->assertSame(['feed', 'reason'], array_keys($this->warnings[0]->context));
        $this->assertStringNotContainsString(
            self::CANARY,
            $this->warnings[0]->message.json_encode($this->warnings[0]->context)
        );
    }

    public static function failuresCarryingTheCanary(): array
    {
        return [
            'JSON error body' => ['answers 500 with a JSON error'],
            'HTML error body' => ['answers 503 with an HTML page'],
            'exception message' => ['times out'],
            'unparsable body' => ['answers broken JSON'],
        ];
    }

    private function failure(string $failure, string $feed)
    {
        return match ($failure) {
            'answers 500 with a JSON error' => Http::response(['message' => 'Server Error', 'email' => self::CANARY], 500),
            'answers 503 with an HTML page' => Http::response('<html>'.self::CANARY.'</html>', 503),
            'answers 404' => Http::response(['message' => 'Not Found'], 404),
            'answers 500 around a good list' => Http::response($this->wrapped($feed, $this->rows($feed)), 500),
            'times out' => Http::failedConnection('cURL error 28: timed out reading '.self::CANARY),
            'answers broken JSON' => Http::response('{"data": [{"title": "'.self::CANARY, 200),
            'answers HTML with a 200' => Http::response('<html>Back soon</html>', 200),
            'answers a JSON error with a 200' => Http::response(['message' => 'Server Error'], 200),
            'answers a list of scalars' => Http::response($this->wrapped($feed, [1, 2])),
            'answers an object where the list belongs' => Http::response($this->wrapped($feed, ['title' => 'One Row'])),
        };
    }

    /** Fakes all three feeds as healthy, apart from any response passed in for a named feed. */
    private function fakeFeeds(array $overrides = []): void
    {
        $stubs = [];
        foreach (self::FEEDS as $feed) {
            $stubs["*/api/$feed"] = $overrides[$feed] ?? Http::response($this->wrapped($feed, $this->rows($feed)));
        }

        Http::fake($stubs);
    }

    /** /api/events answers a bare list; the other two are Laravel resource collections. */
    private function wrapped(string $feed, array $rows): array
    {
        return $feed === 'events' ? $rows : ['data' => $rows];
    }

    /** The feed's healthy rows, plus one more carrying these values. */
    private function withExtraRow(string $feed, array $values)
    {
        $row = match ($feed) {
            'events' => $this->eventRow($values),
            'masterclasses' => $this->classRow($values),
            'globalmanevents' => $this->manRow($values),
        };

        return Http::response($this->wrapped($feed, [...$this->rows($feed), $row]));
    }

    private function rows(string $feed): array
    {
        return match ($feed) {
            'events' => [
                $this->eventRow(['postTitle' => 'Club Meeting Row', 'getId' => '101']),
                $this->eventRow(['postTitle' => 'Webinar Row', 'getId' => '102', 'webinar' => '1']),
                $this->eventRow(['postTitle' => 'Member Event Row', 'getId' => '', 'isMember' => '1']),
            ],
            'masterclasses' => [
                $this->classRow(['title' => 'Upcoming Class Row', 'is_past' => false]),
                $this->classRow(['title' => 'Past Class Row', 'is_past' => true, 'video_recording_url' => null]),
            ],
            'globalmanevents' => [
                $this->manRow(['title' => 'Upcoming Man Row', 'is_past' => false]),
                $this->manRow(['title' => 'Past Man Row', 'is_past' => true, 'video_recording_url' => null]),
            ],
        };
    }

    /** The shape of the Lounge's format_event(), cut down to what these views read plus a neighbour. */
    private function eventRow(array $overrides): array
    {
        return array_merge([
            'eventClub' => 'london',
            'webinar' => '0',
            'eventurl' => 'https://events.test/london',
            'timezone' => 'Europe/London',
            'start' => '18:00:00',
            'postId' => 901,
            'start_time' => '18:00:00',
            'start_date' => '2030-01-15',
            'postTitle' => 'A Row',
            'postImage' => 'https://images.test/a.jpg',
            'getId' => '100',
            'event_ttype' => 'Networking',
            'isMember' => '0',
        ], $overrides);
    }

    private function classRow(array $overrides): array
    {
        return array_merge([
            'id' => 1,
            'start_time' => '10:00',
            'start_date' => '2030-02-01',
            'title' => 'A Row',
            'masterclass_url' => 'https://lounge.test/join',
            'masterclass_banner' => 'https://images.test/c.jpg',
            'video_recording_url' => 'https://lounge.test/recording',
            'is_past' => false,
        ], $overrides);
    }

    private function manRow(array $overrides): array
    {
        return array_merge([
            'id' => 1,
            'start_time' => '19:00',
            'start_date' => '2030-03-01',
            'title' => 'A Row',
            'event_url' => 'https://globalman.test/join',
            'event_banner' => 'https://images.test/m.jpg',
            'video_recording_url' => 'https://globalman.test/recording',
            'is_past' => false,
        ], $overrides);
    }

    private function assertWarnings(array $contexts): void
    {
        $this->assertSame($contexts, array_column($this->warnings, 'context'));
    }

    private function assertPageFurniture($response): void
    {
        foreach (self::PAGE_FURNITURE as $text) {
            $response->assertSee($text);
        }
    }

    private function assertRowsShown($response, string $feed): void
    {
        foreach (self::ROWS_ON_THE_PAGE[$feed] as $row) {
            $response->assertSee($row);
        }
    }

    private function assertRowsHidden($response, string $feed): void
    {
        foreach (self::ROWS_ON_THE_PAGE[$feed] as $row) {
            $response->assertDontSee($row);
        }
    }
}
