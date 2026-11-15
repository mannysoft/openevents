<?php

namespace App\Http\Controllers;

use App\Gateways\EventGateway;
use App\Services\GoHighLevel;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Event;
use App\Models\LoungeClub;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Log;
use Illuminate\Support\Facades\Http;

class LandingController extends Controller
{
    /** Seconds per Lounge feed. The homepage calls them in turn, so a hung Lounge costs three. */
    private const LOUNGE_FEED_TIMEOUT = 5;
    /** Seconds per Lambda feed. A page makes one such call, and a Lambda can start cold. */
    private const LAMBDA_FEED_TIMEOUT = 10;
    private const FEED_CONNECT_TIMEOUT = 2;

    private const CALENDAR_FEED_URL = 'https://cz7qklu85m.execute-api.eu-west-2.amazonaws.com/prod/fetchvirtualevent';
    private const CLUB_EVENTS_FEED_URL = 'https://pkdzegt175.execute-api.eu-west-2.amazonaws.com/prod/getclubevents';

    // The keys the pages read from a row of each feed. A row without one of them would throw, so
    // it makes the whole answer malformed.
    private const EVENT_ROW_KEYS = [
        'isMember', 'getId', 'webinar', 'timezone', 'start', 'start_date', 'start_time',
        'eventurl', 'postId', 'postImage', 'postTitle', 'event_ttype',
    ];
    private const MASTERCLASS_ROW_KEYS = [
        'is_past', 'video_recording_url', 'masterclass_url', 'masterclass_banner',
        'start_date', 'start_time', 'title',
    ];
    private const GLOBAL_MAN_ROW_KEYS = [
        'is_past', 'video_recording_url', 'event_url', 'event_banner',
        'start_date', 'start_time', 'title',
    ];
    private const MASTERCLASS_PAGE_ROW_KEYS = [...self::MASTERCLASS_ROW_KEYS, 'description'];
    private const CALENDAR_ROW_KEYS = ['postTitle', 'webinar', 'postImage', 'postDescription', 'start', 'end'];
    private const CLUB_EVENT_ROW_KEYS = ['start_date', 'start_time', 'eventUrl', 'eventType', 'postImage', 'postTitle'];

    /**
     * redirect index page
     * @param  Request $request http request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function showLandingPage()
    {
        $login = 0;
        $admin = 0;

        $avatar = session('avatar');
        $firstname = session('firstname');
        $lastname = session('lastname');
        $email = session('email');

        if (session('access_token')) {
            $login = 1;
        }
        if (session('advanced')) {
            $admin = 1;
        }

        // Events
        $eventsData = $this->withReadableDates($this->fetchLoungeFeed('/api/events', self::EVENT_ROW_KEYS));

        // Masterclass
        $masterclasses = collect($this->fetchLoungeFeed('/api/masterclasses', self::MASTERCLASS_ROW_KEYS, 'data'));
        $masterclassesPresent = $masterclasses->where('is_past', false)->all();

        $masterclassesPast = $masterclasses->where('is_past', true)->all();

        // Global Man Events
        $globalManEvents = collect($this->fetchLoungeFeed('/api/globalmanevents', self::GLOBAL_MAN_ROW_KEYS, 'data'));

        $data = array(
            'eventsData' => $eventsData,
            'masterclassesPresent' => $masterclassesPresent,
            'masterclasses' => $masterclassesPast,
            'globalManEvents' => $globalManEvents,
            'loginData' => $login,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'avatar' => $avatar,
            'admin' => $admin
        );
        return view('Public.Home.index')->with($data);
    }

    private function fetchLoungeFeed(string $path, array $rowKeys, ?string $listKey = null): array
    {
        $get = fn () => Http::withOptions(['verify' => false])
            ->connectTimeout(self::FEED_CONNECT_TIMEOUT)
            ->timeout(self::LOUNGE_FEED_TIMEOUT)
            ->get(config('app.lounge_url') . $path);

        return $this->readFeed($path, $rowKeys, $listKey, $get);
    }

    private function fetchLambdaFeed(string $url, array $query, array $rowKeys): array
    {
        $get = fn () => Http::connectTimeout(self::FEED_CONNECT_TIMEOUT)
            ->timeout(self::LAMBDA_FEED_TIMEOUT)
            ->get($url, $query);

        return $this->readFeed(parse_url($url, PHP_URL_PATH), $rowKeys, 'data', $get);
    }

    /**
     * A feed that fails, hangs or answers anything else comes back empty, so it costs its own
     * section of the page and not the whole page.
     */
    private function readFeed(string $feed, array $rowKeys, ?string $listKey, callable $get): array
    {
        try {
            $response = $get();
        } catch (\Throwable $e) {
            return $this->dropFeed($feed, class_basename($e));
        }

        if (!$response->successful()) {
            return $this->dropFeed($feed, 'HTTP ' . $response->status());
        }

        $rows = $response->json($listKey);

        if (!$this->isListOfRows($rows, $rowKeys)) {
            return $this->dropFeed($feed, 'malformed answer');
        }

        // Printing an array throws. Other keys can hold one: the Lounge's rows carry avatarArray.
        return $this->keepRows($feed, $rows, 'value the page cannot print', fn (array $row) => !array_filter(
            array_intersect_key($row, array_flip($rowKeys)),
            'is_array'
        ));
    }

    private function isListOfRows(mixed $rows, array $keys): bool
    {
        if (!is_array($rows)) {
            return false;
        }

        foreach ($rows as $row) {
            if (!is_array($row) || array_diff_key(array_flip($keys), $row)) {
                return false;
            }
        }

        return true;
    }

    /** Logs the path and a reason only: a body can hold anything, so it stays out of the log. */
    private function dropFeed(string $feed, string $reason): array
    {
        Log::warning('Public page: feed dropped, its section stays empty', [
            'feed' => $feed,
            'reason' => $reason,
        ]);

        return [];
    }

    /** One bad row costs that row. Logs a count for the same reason dropFeed() logs no body. */
    private function keepRows(string $feed, array $rows, string $reason, callable $isGood): array
    {
        $kept = array_filter($rows, $isGood);

        if (count($kept) < count($rows)) {
            Log::warning('Public page: feed rows dropped, the rest still show', [
                'feed' => $feed,
                'reason' => $reason,
                'rows' => count($rows) - count($kept),
            ]);
        }

        return $kept;
    }

    /**
     * The homepage hands these values to DateTime, which throws on one it cannot read and would
     * take the page with it. A bad date costs its row. A bad timezone only moves a countdown, so
     * it reads as blank and the row stays.
     */
    private function withReadableDates(array $rows): array
    {
        $rows = $this->keepRows('/api/events', $rows, 'unreadable date', function (array $row) {
            return $this->parses(\DateTime::class, $row['start'])
                && $this->parses(\DateTime::class, $row['start_date']);
        });

        $badTimezones = 0;
        foreach ($rows as &$row) {
            if ($row['timezone'] && !$this->parses(\DateTimeZone::class, $row['timezone'])) {
                $row['timezone'] = null;
                $badTimezones++;
            }
        }
        unset($row);

        if ($badTimezones) {
            Log::warning('Public page: bad timezones read as blank, those rows still show', [
                'feed' => '/api/events',
                'reason' => 'unreadable timezone',
                'rows' => $badTimezones,
            ]);
        }

        return $rows;
    }

    private function parses(string $dateClass, mixed $value): bool
    {
        try {
            new $dateClass((string) $value);
        } catch (\Throwable) {
            return false;
        }

        return true;
    }

    public function showClubsPage(Request $request)
    {
        // Get the clubs from database
        $login = 0;
        $admin = 0;
        $avatar = session('avatar');
        $firstname = session('firstname');
        $lastname = session('lastname');
        $email = session('email');

        if (session('access_token')) {
            $login = 1;
        }
        if (session('advanced')) {
            $admin = 1;
        }

        $clubs = LoungeClub::query()
            ->where('slug', '!=', 'none')
            ->where('is_active', true)
            ->orderBy('country')
            ->get();

        $data = array(
            'loginData' => $login,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'avatar' => $avatar,
            'admin' => $admin,
            'clubs' => $clubs
        );
        
        return view('Public.Home.clubs')->with($data);
    }

    public function showClubEventPage($club)
    {
        if ($club == 'online') {
            $club = 'accra';
        }
        $login = 0;
        $admin = 0;

        $avatar = session('avatar');
        $firstname = session('firstname');
        $lastname = session('lastname');
        $email = session('email');

        if (session('access_token')) {
            $login = 1;
        }
        if (session('advanced')) {
            $admin = 1;
        }

        $EventsData = $this->fetchLambdaFeed(self::CLUB_EVENTS_FEED_URL, ['clubName' => $club], self::CLUB_EVENT_ROW_KEYS);
        $data = array(
            'eventsData' => $EventsData,
            'loginData' => $login,
            'club' => $club,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'avatar' => $avatar,
            'admin' => $admin
        );

        // return redirect('https://globalwomanevents.com/clubs');
        return view('Public.Home.clubevents')->with($data);
    }
    public function showEventsPage(Request $request)
    {
        $login = 0;
        $admin = 0;
        $avatar = session('avatar');
        $firstname = session('firstname');
        $lastname = session('lastname');
        $email = session('email');

        if (session('access_token')) {
            $login = 1;
        }
        if (session('advanced')) {
            $admin = 1;
        }

        $eventCalendar = array();
        $EventsData = $this->fetchLambdaFeed(
            self::CALENDAR_FEED_URL,
            ['limit' => 50, 'offset' => 0, 'userId' => 1],
            self::CALENDAR_ROW_KEYS
        );
        // A row's link comes from one of two keys, so neither can be demanded of every row.
        $EventsData = $this->keepRows('/prod/fetchvirtualevent', $EventsData, 'no usable link', function (array $row) {
            $link = $row['webinar'] != "1" ? 'getId' : 'eventurl';

            return array_key_exists($link, $row) && !is_array($row[$link]);
        });
        $i = 0;
        foreach ($EventsData as $event1) {
            $eventCalendar[$i]['title'] = $event1['postTitle'];
            if ($event1['webinar'] != "1") {
                $eventCalendar[$i]['eventurl'] = config('app.url') ."/club/" . $event1['getId'];
            } else {
                $eventCalendar[$i]['eventurl'] = $event1['eventurl'];
            }

            $eventCalendar[$i]['image'] = $event1['postImage'];
            $eventCalendar[$i]['description'] = $event1['postDescription'];
            $eventCalendar[$i]['start'] = $event1['start'];
            $eventCalendar[$i]['end'] = $event1['end'];
            $eventCalendar[$i]['className'] = 'fc-bg-default';
            $eventCalendar[$i]['icon'] = 'rocket';
            $i++;
        }
        $data = array(
            'eventsData' => $eventCalendar,
            'loginData' => $login,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'avatar' => $avatar,
            'admin' => $admin
        );
        return view('Public.Home.events')->with($data);
        ;
    }

    public function showMasterclassPage(Request $request)
    {
        $eventCalendar = collect($this->fetchLoungeFeed('/api/masterclasses', self::MASTERCLASS_PAGE_ROW_KEYS, 'data'))
            ->map(fn (array $masterclass): array => [
                'title' => $masterclass['title'],
                'eventurl' => $masterclass['is_past']
                    ? $masterclass['video_recording_url']
                    : $masterclass['masterclass_url'],
                'image' => $masterclass['masterclass_banner'],
                'description' => $masterclass['description'],
                'start' => $masterclass['start_date'] . 'T' . $masterclass['start_time'],
                'className' => 'fc-bg-default',
                'icon' => 'rocket',
            ])
            ->values()
            ->all();

        $data = array(
            'eventsData' => $eventCalendar,
            'loginData' => session('access_token') ? 1 : 0,
            'firstname' => session('firstname'),
            'lastname' => session('lastname'),
            'email' => session('email'),
            'avatar' => session('avatar'),
            'admin' => session('advanced') ? 1 : 0,
        );

        return view('Public.Home.masterclass')->with($data);
    }

    public function showMastermindPage(Request $request)
    {
        return view('Public.Home.mastermind');
    }
    public function showOrdersPage(Request $request)
    {
        return view('Public.Home.orders');
    }

    /**
     * "Book my spot" on a club event page. It is the same registration an order makes, so it
     * runs through EventGateway::createUser(), which carries on without a Lobby account when
     * the Lobby cannot be reached. The copy of that code that lived here, with its own
     * checkEmailExists(), had no way round a Lobby that was down.
     */
    public function saveUserDataEvent(Request $request)
    {
        $eventGateway = new EventGateway();
        $eventGateway->email = $request->input('email');
        $eventGateway->firstName = $request->input('firstName');
        $eventGateway->lastName = $request->input('lastName');
        $eventGateway->mobileNo = $request->input('mobileNo');
        $eventGateway->eventId = $request->input('eventId');
        $eventGateway->club = $request->input('club');

        return $eventGateway->createUser();
    }

    public function checkCoupon(Request $request)
    {
        session(['percent_off' => null]);

        session(['couponCode' => null]);

        session(['discountAmount' => null]);

        // Special20 20%
        $event = Event::findOrFail($request->event_id);

        $eventClub = $event->club;

        // Check if the coupon exists
        $coupon = DB::connection('mysql_lounge')
            ->table('coupons')
            ->where('gateway_id', $request->coupon)
            ->first();

        // If exists then check if club is not null
        if ($coupon) {
            if ($coupon->club != null) {
                if ($coupon->club != $eventClub) {
                    abort(400);
                    return 'error';
                }
            }
        }

        $activeAccountPaymentGateway = $event->account->getGateway($event->account->payment_gateway_id);

        $client = new \GuzzleHttp\Client();

        try {
            $clientRequest = $client->request(
                "GET",
                'https://api.stripe.com/v1/coupons/' . $request->coupon,
                [
                    'auth' => [
                        $activeAccountPaymentGateway->config['apiKey'],
                        $activeAccountPaymentGateway->config['publishableKey']
                    ],

                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
                        'Accept'     => 'application/json',
                    ]
                ]
            );

            $response = $clientRequest->getBody();

            $json = json_decode($response, true);
        } catch (\Exception $e) {
            $json['valid'] = false;
            $json['message'] = 'Invalid coupon code';
            $json['discounted_amount'] = number_format($request->event_price, 2);
            return response()->json($json, 400);
        }

        if (isset($json['valid']) && $json['valid'] == true) {
            session(['percent_off' => $json['percent_off']]);
            // Return the discounted amount
            $json['discount_amount'] = ($json['percent_off'] / 100) * $request->event_price;
            $json['event_price'] = $request->event_price - $json['discount_amount'];

            session(['couponCode' => $request->coupon]);
            session(['discountAmount' => $json['discount_amount']]);

            // Update the order session
            session(['ticket_order_' . $request->event_id . '.discountAmount' => $json['discount_amount']]);
            session(['ticket_order_' . $request->event_id . '.discount_amount' => $json['discount_amount']]);
            session(['ticket_order_' . $request->event_id . '.order_total' => $request->event_price - $json['discount_amount']]);
        }

        // Include the discounted amount
        // $eventGateway = new EventGateway;
        // $order = $eventGateway->getOrder($request->event_id);
        // $json['discounted_amount'] = $order->getOrderTotalWithBookingFee(true);
        $json['discount_amount'] = number_format($json['discount_amount'], 2);
        $json['discounted_amount'] = number_format(session('ticket_order_' . $request->event_id . '.order_total'), 2);

        return $json;
    }

    public function saveUserDataLondon(Request $request)
    {
        $currentTime = Carbon::now();
        $email = $request->input('email');
        $firstName = $request->input('firstName');
        $lastName = $request->input('lastName');
        $mobileNo = $request->input('mobileNo');
        $addressuser = $request->input('addressuser');
        $eventId = $request->input('eventId');
        $club = $request->input('club');

        DB::table('Wo_Clickfunnel_Event_London_Data')->insert(
            ['email' => $email, 'first_name' => $firstName, 'last_name' => $lastName, 'mobile_no' => $mobileNo, 'created' => $currentTime, 'club' => $club, 'event_id' => $eventId, 'address' => $addressuser, 'event_id' => $eventId]
        );
        return response()->json(['status' => "success"]);
    }

    // https://globalwomanevents.com/saveContactGHL?first_name=Max&last_name=Ice&email=testg@gmail.com&mobile=+121212121&tags=events%20test

    public function saveContactGHL(Request $request)
    {
        $ghl = new GoHighLevel();
        $locationId = $ghl->locationId;
        $response = $ghl->createUpdateContact([
            'firstName' => $request->first_name,
            'lastName' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->mobile,
            'locationId' => $locationId,
            'tags' => [$request->tags]
        ]);
    }

    public function getGHLToken(Request $request)
    {
        // dd(1);
        $ghl = new GoHighLevel();
        $ghl->setCode($request->code);
        return $ghl->getAccessToken();
    }
}
