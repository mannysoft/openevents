<?php

use GuzzleHttp\Client;
use App\Models\Order;
use App\Models\EmailSchedule;
use App\Models\LoungeClub;
use Illuminate\Support\Facades\Auth;

if (!function_exists('money')) {
    /**
     * Format a given amount to the given currency
     *
     * @param $amount
     * @param \App\Models\Currency $currency
     * @return string
     */
    function money($amount, \App\Models\Currency $currency)
    {
        return $currency?->code . number_format(
            $amount,
            $currency->decimal_place,
            $currency->decimal_point,
            $currency->thousand_point
        ) . $currency->symbol_right;
    }
}

if (!function_exists('fetch_user_status')) {
    /**
     * Fetch the raw user status payload from the lounge API for the given email.
     *
     * The response includes at least `status` (Active|Expired|null) and
     * `app_user_type` (mentor|entrepreneur|influencer|director|lifetime|free|null).
     * Results are cached per-request to avoid duplicate HTTP calls.
     *
     * @return array<string, mixed>
     */
    function fetch_user_status(?string $email): array
    {
        static $cache = [];

        if ($email === null || $email === '' || $email === 'test@gmail.com') {
            return [];
        }

        if (session('events_email')) {
            $email = session('events_email');
        }

        $key = strtolower($email);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        try {
            $client = new Client(['verify' => false, 'timeout' => 10]);
            $result = $client->get(config('app.lounge_url') . '/api/users/' . $email . '/status');
            $json = json_decode((string) $result->getBody(), true);
        } catch (\Throwable $e) {
            $json = [];
        }

        return $cache[$key] = is_array($json) ? $json : [];
    }
}

if (!function_exists('is_member')) {

    function is_member($email)
    {
        if ($email == 'test@gmail.com') {
            return '0';
        }

        if (session('events_email')) {
            $email = session('events_email');
        }

        $member = '0';
        if ($email == null) {
            return $member;
        }

        $json = fetch_user_status($email);

        if (isset($json['status'])) {
            if ($json['status'] == 'Active') {
                $member = '1';

                session(['referal_first_name' => $json['first_name'] ?? null]);
                session(['referal_last_name' => $json['last_name'] ?? null]);
            }

            session(['is_allowed_attend_event' => $json['is_allowed_attend_event'] ?? false]);
            session(['app_user_type' => $json['app_user_type'] ?? null]);
            session(['is_attended_event' => $json['is_attended_event'] ?? false]);
        }

        return $member;
    }
}

if (!function_exists('is_mentor')) {
    /**
     * Determine if the given email belongs to an active Mentors Club member.
     *
     * Mentors Club is the entry-level membership tier and is NOT permitted to
     * book in-person event tickets — they must upgrade to Entrepreneur or
     * Influencer. Online / hybrid-online tickets remain allowed.
     */
    function is_mentor(?string $email): bool
    {
        if ($email === null || $email === '' || $email === 'test@gmail.com') {
            return false;
        }

        $json = fetch_user_status($email);

        if (($json['status'] ?? null) !== 'Active') {
            return false;
        }

        return ($json['app_user_type'] ?? null) === 'mentor';
    }
}

if (!function_exists('get_points')) {

    function get_points($email)
    {
        if (session('events_email')) {
            $email = session('events_email');
        }

        if ($email == null || $email == 'test@gmail.com') {
            return 0;
        }

        // Call API then identify if the subscription is Expired or Active status
        $client = new Client(['verify' => false]);
        $result = $client->get(config('app.lounge_url') . '/api/users/' . $email . '/points');

        $resData = $result->getBody();

        $json = json_decode($resData, true);

        if (isset($json['points']) && $json['points'] != null) {
            return $json['points'];
        }

        return 0;
    }
}

if (!function_exists('cities')) {

    function cities($cityId)
    {
        return true;
        $cities = [
            'london',
            'amsterdam',
            'berlin',
            'vancouver',
            'toronto',
            'manchester',
            'geneva',
            'accra',
            'online',
            'oslo',
            'bucharest',
            'paris',
            'stockholm',
            'johannesburg',
            'losangeles',
            'manila',
            'mongolia',
            'brussels',
            'cyprus',
            'chicago',
            'nottingham',
            'frankfurt',
            'vienna',
            'singapore',
            'newyork',
            'tirana',
            'birmingham',
            'india',
            'dubai',
            'windsor',
            'florence',
            'skopje',
            'orangecounty',
            'zurich',
            'kosovo',
            'doha',
            'nottinghill',
            'istanbul',
            'london-mayfair',
            'globalmanclub',
            'siliconvalley',
            'investorsclub',
            'munich',
            'metrodc',
            'houston',
            'dallas',
            'liverpool',
            'cheshire',
            'lisbon',
            'cobham',
            'malta',
            'leeds',
            'norwich',
            'essex',
        ];

        if (in_array($cityId, $cities)) {
            return true;
        }

        abort(404);
    }
}

if (!function_exists('is_default_template')) {

    function is_default_template($club)
    {
        // We can create array from clubs table and check if default template
        // Cache the clubs for 1 hour
        $clubs = Cache::remember('cached_clubs_slugs_default_template', 3600, function () {
            return App\Models\LoungeClub::query()
                ->where('slug', '!=', 'none')
                ->where('is_default_template', true)
                ->pluck('slug')
                ->toArray();
        });

        return in_array($club, $clubs);
    }
}

if (!function_exists('show_fb_pixels')) {

    function show_fb_pixels($club)
    {
        // An event with no club has no pixel, and get_pixel_id() needs a slug.
        return $club !== null;
        // Skip all for now
        return in_array($club, [
            'manchester',
            'geneva',
            'munich',
            'london',
            'brussels',
            'dallas',
            'houston',
            'metrodc',
            'tirana',
            'toronto',
            'zurich',
        ]);
    }
}

if (!function_exists('get_pixel_id')) {

    function get_pixel_id(string $club)
    {
        $pixelId = '1347934389196200';

        $club = LoungeClub::where('slug', $club)->first();

        if ($club) {
            $pixelId = $club->meta_pixel;
            Log::info('Club found: ' . $club);
            Log::info('Club meta pixel: ' . $club->meta_pixel);
        } else {
            Log::info('Club not found: ' . $club);
        }

        return $pixelId ?? '1347934389196200';
    }
}

if (!function_exists('skip_agenda')) {

    function skip_agenda($club)
    {
        // Skip all for now
        return true;

        $clubs = [
            'orangecounty',
            'zurich',
            'london',
        ];

        return in_array($club, $clubs);
    }
}

// Clubs for route like /london /losangeles /amsterdam etc.
if (!function_exists('clubs')) {

    function clubs()
    {
        return Cache::remember('cached_clubs_slugs', 3600, function () {
            return App\Models\LoungeClub::query()
                ->where('slug', '!=', 'none')
                ->pluck('slug')
                ->toArray();
        });

        return [
            'london',
            'amsterdam',
            'berlin',
            'vancouver',
            'toronto',
            'manchester',
            'geneva',
            'accra',
            'online',
            'oslo',
            'bucharest',
            'paris',
            'stockholm',
            'johannesburg',
            'losangeles',
            'manila',
            'mongolia',
            'brussels',
            'cyprus',
            'chicago',
            'nottingham',
            'frankfurt',
            'vienna',
            'singapore',
            'newyork',
            'tirana',
            'birmingham',
            'india',
            'dubai',
            'windsor',
            'florence',
            'skopje',
            'orangecounty',
            'zurich',
            'kosovo',
            'doha',
            'nottinghill',
            'istanbul',
            'london-mayfair',
            'globalmanclub',
            'siliconvalley',
            'investorsclub',
            'munich',
            'albaniandiaspora',
            'abudhabi',
            'metrodc',
            'houston',
            'dallas',
            'liverpool',
            'cheshire',
            'lisbon',
            'cobham',
            'malta',
            'leeds',
            'notingham',
            'norwich',
            'essex',
        ];
    }
}

if (!function_exists('event_price')) {

    function event_price($isVIP = false, $event, $member, $isHybrid = false, $isBringFriend = false)
    {
        if ($member == "1") {
            $evenPrice = $event->mprice;
            if (isset($isVIP) && $isVIP == true) {
                $evenPrice = $event->vip_price;
            }

            if ($isHybrid) {
                $evenPrice = $event->hmprice;
            }
        } else {

            $evenPrice = $event->nmprice;

            if (isset($isVIP) && $isVIP == true) {
                $evenPrice = $event->vip_nm_price;
            }

            if ($isHybrid) {
                $evenPrice = $event->hnmprice;
            }

            // Check if the event with first time discount
            if (first_time_guest()) {
                if ($event->first_time_guest_price !== null) {
                    $evenPrice = $event->first_time_guest_price;
                }
            }
        }

        // If bring a friend then the second ticket is 50% off the price of the first ticket.
        // So we will use the bring_friend_price for the second ticket.
        if ($isBringFriend) {
            if ($member == "1") {

                $evenPrice = $event->mprice;
            } else {

                $evenPrice = $event->nmprice;
            }

            $evenPrice = bring_friend_price($evenPrice);
        }

        return $evenPrice;
    }
}

if (!function_exists('event_label')) {

    function event_label($isVIP = false, $event, $member, $isHybrid = false, $isBringFriend = false)
    {
        $currency = 'USD';
        // if ($event->currency_id == "2")
        //     $currency = "€";
        // elseif ($event->currency_id == "3")
        //     $currency = "£";
        // elseif ($event->currency_id == "5")
        //     $currency = "CA$";
        // elseif ($event->currency_id == "17")
        //     $currency = "S$";
        // elseif ($event->currency_id == "27")
        //     $currency = "₺";

        $currency = $event->currency?->code ?? 'USD';

        $vat = "0";
        if ($event->organiser_fee_percentage > 1) {
            $vat = "1";
        }

        if ($member == "1") {

            $amount = $event->mprice;
            if ($isVIP) {
                $amount = $event->vip_price;
            }
            if ($isHybrid) {
                $amount = $event->hmprice;
            }
        } else {

            $amount = $event->nmprice;

            if ($isVIP) {
                $amount = $event->vip_nm_price;
            }

            if ($isHybrid) {
                $amount = $event->hnmprice;
            }

            // Check if the event with first time discount
            if (first_time_guest()) {
                if ($event->first_time_guest_price !== null) {
                    $amount = $event->first_time_guest_price;
                }
            }
        }

        // If bring a friend then the second ticket is 50% off the price of the first ticket.
        // So we will use the bring_friend_price for the second ticket.
        if ($isBringFriend) {
            if ($member == "1") {

                $amount = $event->mprice;
            } else {

                $amount = $event->nmprice;
            }

            $amount = bring_friend_price($amount);
        }

        if ($vat == '1') {
            return $currency . $amount . ' + VAT';
        }

        return $currency . ' ' . $amount;
    }
}

if (!function_exists('first_time_guest')) {
    function first_time_guest()
    {
        $lastname = session('lastname');
        $email = session('email');

        // If no email or last name
        if ($lastname == null || $email == null) {
            return false;
        }

        // Check if the user already purchased
        $purchase = Order::query()
            ->where('email', $email)
            ->where('last_name', $lastname)
            ->first();

        if ($purchase == null) {
            return true;
        }

        return false;
    }
}

if (!function_exists('pricing_label')) {

    function pricing_label($isVIP = false, $member, $isBringFriend = false)
    {
        $label = '';
        if ($member == '1') {
            if ($isVIP == true) {
                $label = 'VIP Member Ticket';
            } else {
                $label = 'Member General Ticket';
            }
        } else {
            if ($isVIP == true) {
                $label = 'VIP Non-Member ticket';
            } else {
                $label = 'Non-Member General Ticket';
            }
        }

        if ($isBringFriend == true) {
            if ($member == '1') {
                $label = 'Member Bring a Friend Ticket - 2x1';
            } else {
                $label = 'Non-Member Bring a Friend Ticket - 2x1';
            }
        }

        return $label;
    }
}

if (!function_exists('bring_friend_price')) {
    function bring_friend_price($ticketPrice)
    {
        $amountLess = $ticketPrice / 2;
        return ($ticketPrice * 2) - $amountLess;
    }
}

if (!function_exists('is_admin')) {

    function is_admin()
    {
        if (session('email') == null) {
            return false;
        }

        $email = session('email');

        $user = DB::connection('mysql_lounge')
            ->table('users')
            ->where('email', $email)
            ->first();

        if ($user == null) {
            return false;
        }

        $userRole = DB::connection('mysql_lounge')
            ->table('user_roles')
            ->where('user_id', $user->id)
            ->first();

        if ($userRole) {
            if ($userRole->role_id == 1 || $userRole->role_id == 2 || $userRole->role_id == 3) {
                return true;
            }
        }

        return false;
    }
}

use App\Services\Order as OrderService;
use Illuminate\Support\Facades\Log;

if (!function_exists('generate_pdf_receipt')) {

    function generate_pdf_receipt($order)
    {
        $orderService = new OrderService($order->amount, $order->organiser_booking_fee, $order->event);
        $orderService->calculateFinalCosts();
        $data = [
            'order' => $order,
            'orderService' => $orderService
        ];
        $fileName = $order->order_reference;
        $filePath = public_path(config('attendize.event_pdf_tickets_path')) . '/' . $fileName . '.pdf';

        // https://github.com/barryvdh/laravel-dompdf
        $pdf = App::make('dompdf.wrapper');
        // $pdf->loadHTML('<h1>Test</h1>');
        $pdf->loadView('receipt', $data);
        $pdf->save($filePath);

        return $filePath;
        // return $pdf->stream();
    }
}

if (!function_exists('is_event_coming')) {

    function is_event_coming($event)
    {
        // Check if the event is coming in <= 3 days
        $startDate = $event->start_date;

        $days = $startDate->diffInDays(now());

        // If only 3 days remaining
        if ($days <= 3) {
            return true;
        }

        return false;
    }
}

if (!function_exists('cookie_expires')) {

    function cookie_expires()
    {
        return time() + 10 * 365 * 24 * 60 * 60;
    }
}

if (!function_exists('convert_youtube_video')) {

    function convert_youtube_video($videoUrl)
    {
        // Check if the video is from youtube
        $containsYoutube = Str::contains($videoUrl, 'youtube.com/watch?v=');

        if ($containsYoutube) {
            $videoId = Str::after($videoUrl, 'youtube.com/watch?v=');

            $video = explode('&', $videoId);

            return 'https://www.youtube.com/embed/' . $video[0];
        }

        return $videoUrl;
    }
}

if (!function_exists('add_email_schedule')) {

    function add_email_schedule($firstName, $lastName, $email, $eventId, $mobileNo, $club)
    {
        $typesDates[1] = now()->format('Y-m-d');
        $typesDates[2] = now()->addDays(1)->format('Y-m-d');
        $typesDates[3] = now()->addDays(2)->format('Y-m-d');


        // 3 days Before the event
        $event = \App\Models\Event::find($eventId);

        if ($event->event_date == null) {
            return;
        }

        $eventDate = \Carbon\Carbon::createFromFormat('Y-m-d', $event->event_date);

        $typesDates[-3] = $eventDate->subDays(3)->format('Y-m-d');
        $typesDates[-2] = $eventDate->addDays(1)->format('Y-m-d');
        $typesDates[-1] = $eventDate->addDays(1)->format('Y-m-d');

        // Added 3(3 days after registration) and -2(2 days before the event)
        // Email 1 - You Started Your Registration - Before the event Not buy a ticket
        // Email 2 – Day 2: What Happens When Women Come Together
        // Email 3 – Day 3: Invest in Yourself
        // Email 4 – Two days Before the event: Final Reminder
        // GHL Workflow:
        // https://app.gohighlevel.com/location/ZDavdc0F1sm0KU0x8ggQ/workflow/192b1349-ff7f-402f-ab3f-fff3b6963fb9


        // After the event
        $eventDate = \Carbon\Carbon::createFromFormat('Y-m-d', $event->event_date);

        $typesDates[31] = $eventDate->addDays(1)->format('Y-m-d');
        $typesDates[32] = $eventDate->addDays(1)->format('Y-m-d');
        $typesDates[34] = $eventDate->addDays(2)->format('Y-m-d');
        $typesDates[36] = $eventDate->addDays(2)->format('Y-m-d');

        foreach ($typesDates as $type => $dateSend) {
            create_email_schedule($firstName, $lastName, $email, $mobileNo, $eventId, $club, $type, $dateSend);
        }
    }
}

if (!function_exists('create_email_schedule')) {

    function create_email_schedule($firstName, $lastName, $email, $mobileNo, $eventId, $club, $type, $dateSend)
    {
        EmailSchedule::firstOrCreate([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'mobile_no' => $mobileNo,
            'event_id' => $eventId,
            'club' => $club,
            'type' => $type,
            'date_send' => $dateSend ?? now()->format('Y-m-d'),
        ]);
    }
}

if (!function_exists('is_points_enough')) {

    function is_points_enough($event, $orderTotal)
    {
        $pointsEnough = false;
        // Check if the points is enough to pay for the event
        $email = session('email') ?? 'test@gmail.com';

        $totalPoints = get_points($email);

        $gbpAmount = $orderTotal;

        // If the currency is not GBP, convert it to GBP
        if ($event->currency->code !== 'GBP') {
            $gbpAmount = \App\Models\Currency::getEquivalentRate($event->currency->code, $orderTotal, ['GBP']);
            $gbpAmount = $gbpAmount[0]['converted_rate'];
        }

        // dd($gbpAmount); // 25.85


        // dd($gbpAmount * 100); // 25.85


        // dd($gbpAmount); // 25.85

        // Convert the points to GBP. 100 points = 1 GBP
        $pointsTogbp = $totalPoints / 100;

        if ($pointsTogbp >= $gbpAmount) {
            $pointsEnough = true;
        }

        return $pointsEnough;
    }
}

if (!function_exists('sortable_link')) {
    /**
     * Format a given amount to the given currency
     *
     * @param $amount
     * @param \App\Models\Currency $currency
     * @return string
     */

    function sortable_link($title, $active_sort, $sort_by, $sort_order, $url_params = [], $class = '', $extra = '')
    {

        $sort_order = $sort_order == 'asc' ? 'desc' : 'asc';

        $url_params = http_build_query([
            'sort_by'    => $sort_by,
            'sort_order' => $sort_order,
        ] + $url_params);

        $html = "<a href='?$url_params' class='col-sort $class' $extra>";

        $html .= ($active_sort == $sort_by) ? "<b>$title</b>" : $title;

        $html .= ($sort_order == 'desc') ? '<i class="ico-arrow-down22"></i>' : '<i class="ico-arrow-up22"></i>';

        $html .= '</a>';

        return $html;
    }
}

if (!function_exists('generate_referal_code')) {

    function generate_referal_code()
    {
        $memberEmail = session('email');
        // URL-safe base64 encode. To get email back: base64_decode(strtr($refCode, '-_', '+/'))
        $refCode = rtrim(strtr(base64_encode($memberEmail), '+/', '-_'), '=');
        $currentUrl = url()->current();
        $inviteUrl = $currentUrl . (str_contains($currentUrl, '?') ? '&' : '?') . 'ref=' . $refCode;

        return $inviteUrl;
    }
}

if (!function_exists('store_club_commission')) {

    function store_club_commission($code = null)
    {
        // Make sure to clear the club_commission session when user visit the 
        // event page, so that it will not cause issue for other clubs if user visit multiple clubs
        // TODO Create a unique code to identify the club commission for each club
        // Create a migration to generate a unique code for each club and save it in the lounge_clubs table, 
        // then we can use that code to save the commission in session.
        // For Example  7IRULZ89 - For losangeles
        //              QAM2BOYT - For orangecounty
        if (!empty($code)) {
            $club = LoungeClub::where('unique_code', $code)->first();
            if ($club) {
                Log::info('Club commission code found: ' . $code . ' for club: ' . $club->name);
                session(['club_unique_code' => $code]);
                session(['club_commission' => $club->slug]);
            } else {
                // So we can support other people
                Log::warning('Club commission code not found: ' . $code);
                session(['club_unique_code' => $code]);
                session(['club_commission' => $code]);
            }
        }
    }
}

if (!function_exists('app_version')) {

    function app_version()
    {
        return ' v3.11';
    }
}
