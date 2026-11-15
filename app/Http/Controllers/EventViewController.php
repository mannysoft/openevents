<?php

namespace App\Http\Controllers;

use App\Attendize\PaymentUtils;
use App\Models\Ticket;
use App\Attendize\Utils;
use App\Gateways\EventGateway;
use App\Models\Affiliate;
use App\Models\Event;
use App\Models\EventAccessCodes;
use App\Models\EventStats;
use Illuminate\Support\Facades\Auth;
use Cookie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Validator;
use Spatie\CalendarLinks\Link;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\ReservedTickets;
use App\Models\VisitLog;
use App\Models\LoungeClub;
use App\Models\LoungeEventSection;
use App\Models\LoungeEventUniqueLink;
use App\Models\LoungeMasterclass;
use App\Services\GoHighLevel;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use stdClass;
use Stevebauman\Location\Facades\Location;
use Intervention\Image\ImageManager;

class EventViewController extends Controller
{
    /**
     * Show the homepage for an event
     *
     * @param Request $request
     * @param $event_id
     * @param string $slug
     * @param bool $preview
     * @return mixed
     */
    public function __construct()
    {
        $event_idcheck = isset($_GET['event_id']);
        if ($event_idcheck == 1) {
            $event_id1 = $_GET['event_id'];

            $event = Event::findOrFail($event_id1);

            $eclubs = $event->club;
            session([
                'clubs1' => $eclubs,
                'event_id1' => $event_id1,
            ]);

            sleep(1);

            return redirect()->to(config('app.url') . '/' . $eclubs);
        }

        $app_id = 'dd94cac1a7f8f47192b6'; // your application app id
        $app_secret = 'f351149d710a1b2e2f5b0d64af3520e06c0ce9d'; // your application app secret
        if (empty($_GET['code'])) {
        } else {
            $code1 = isset($_GET['code']);
            if (session('clubs')) {
                $clubDat = session('clubs');
            } else {
                $clubDat = 'london';
            }

            // the GET parameter you got in the callback: http://yourdomain/?code=XXX
            if ($code1 == 1) {

                $code = $_GET['code'];
                $get = $this->curl_get_contents("https://lobby.globalwomanclub.com/authorize?app_id=" . $app_id . "&app_secret=" . $app_secret . "&code=" . $code);

                $json = json_decode($get, true);

                if (isset($json['access_token'])) {
                    $member = "0";

                    $access_token = $json['access_token']; // your access token
                    $reference = $json['reference'];
                    $type = "get_user_data"; // or posts_data
                    $get = $this->curl_get_contents("https://lobby.globalwomanclub.com/app_api?access_token=" . $access_token . "&type=" . $type);
                    $character = json_decode($get, true);
                    $user_id = $character['user_data']['ref_id'];
                    $firstname = $character['user_data']['first_name'];
                    $avatar = $character['user_data']['avatar'];
                    $lastname = $character['user_data']['last_name'];
                    $username = $character['user_data']['username'];
                    $email = $character['user_data']['email'];
                    $phone = $character['user_data']['phone'];
                    $director = $character['user_data']['director'];
                    $admin = 1;
                    $isprotype = $character['user_data']['isprotype'];
                    if ($isprotype == "4" || $isprotype == "3" || $director == "1") {
                        $member = '1';
                    }

                    session([
                        'ref_id' => $user_id,
                        'access_token' => $reference,
                        'member' => $member,
                        'avatar' => $avatar,
                        'email' => $email,
                        // Set only by a completed Lobby sign-in (here and UserLoginController::callback).
                        // The club-page editor trusts this key, never 'email': see verifiedEditorEmail().
                        'events_email' => $email,
                        'phone' => $phone,
                        'firstname' => $firstname,
                        'lastname' => $lastname,
                    ]);

                    if (session('eeventId')) {
                        $eEventId = session('eeventId');

                        return redirect()->to(config('app.url') . '/club/' . $eEventId);
                    }

                    return redirect()->to(config('app.url') . '/' . $clubDat);
                }
            }
        }
    }

    public function curl_get_contents($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        // Without these the call is unbounded: a slow or unreachable Lobby pins this
        // php-fpm worker until the OS gives up on the socket. Enough concurrent
        // requests doing that exhaust the pool, and nginx answers 502.
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $data = curl_exec($ch);

        // The URL carries the app secret and the sign-in code, so it stays out of the log.
        if ($data === false) {
            Log::warning('curl call failed', ['call' => 'lobby-oauth', 'curl_errno' => curl_errno($ch)]);
        }

        curl_close($ch);
        return $data;
    }

    public function showEventHome(Request $request, $event_id, $slug = '', $preview = false)
    {
        $event = Event::findOrFail($event_id);

        if (!Utils::userOwns($event) && !$event->is_live) {
            return view('Public.ViewEvent.EventNotLivePage');
        }

        $data = [
            'event' => $event,
            'tickets' => $event->tickets()->orderBy('sort_order', 'asc')->get(),
            'is_embedded' => 0,
        ];
        /*
         * Don't record stats if we're previewing the event page from the backend or if we own the event.
         */
        if (!$preview && !Auth::check()) {
            $event_stats = new EventStats();
            $event_stats->updateViewCount($event_id);
        }

        /*
         * See if there is an affiliate referral in the URL
         */
        if ($affiliate_ref = $request->get('ref')) {
            $affiliate_ref = preg_replace("/\W|_/", '', $affiliate_ref);

            if ($affiliate_ref) {
                $affiliate = Affiliate::firstOrNew([
                    'name'       => $request->get('ref'),
                    'event_id'   => $event_id,
                    'account_id' => $event->account_id,
                ]);

                ++$affiliate->visits;

                $affiliate->save();

                Cookie::queue('affiliate_' . $event_id, $affiliate_ref, 60 * 24 * 60);
            }
        }

        return view('Public.ViewEvent.EventPage', $data);
    }

    public function online()
    {
        $masterClass = LoungeMasterclass::where('date_time', '>=', now()->format('Y-m-d H:i:s'))->first();

        if ($masterClass) {
            // Redirect to the latest masterclass event
            return redirect()->to($masterClass->masterclass_url);
        }

        // Get the last masterclass event
        $masterClass = LoungeMasterclass::orderBy('date_time', 'desc')
            ->where('date_time', '<=', now()->format('Y-m-d H:i:s'))
            ->first();

        // Redirect to latest masterclass event
        return redirect($masterClass->masterclass_url);
    }

    /**
     * Show preview of event homepage / used for backend previewing
     *
     * @param $event_id
     * @return mixed
     */
    public static function getCalenderIcsAll($calendarName, $eventId)
    {
        $event = Cache::remember('event_detail_' . $eventId, 60, function () use ($eventId) {
            return Event::findOrFail($eventId);
        });

        if ($event->event_type == 0) {
            $address = $event->venue_name . $event->location_address . $event->location_address_line_1 . $event->location_address_line_2 . $event->location_country;
        } elseif ($event->lobby_live == 0) {
            $address = $event->others_url;
        } else {
            $address = $event->lobby_url;
        }

        if ($event->location) {
            $link = Link::create($event->title, $event->start_date, $event->end_date)
                ->description($event->title)
                ->address($event->location);
        } else {
            $finalAddress = "event";
            $link = Link::create($event->title, $event->start_date, $event->end_date)
                ->description($event->title)
                ->address($finalAddress);
        }

        // Need to check the timezone so that we can set the correct timezone
        if ($event->event_timezone != 'Europe/London' && $event->event_timezone != '') {

            $startTime = $event->start_date->format('Y-m-d H:i:s');
            $endTime = $event->end_date->format('Y-m-d H:i:s');

            $startDate = Carbon::createFromFormat('Y-m-d H:i:s', $startTime, $event->event_timezone);
            $endDate = Carbon::createFromFormat('Y-m-d H:i:s', $endTime, $event->event_timezone);

            if ($event->location) {
                $link = Link::create($event->title, $startDate, $endDate)
                    ->description($event->title)
                    ->address($event->location);
            } else {
                $finalAddress = "event";
                $link = Link::create($event->title, $startDate, $endDate)
                    ->description($event->title)
                    ->address($finalAddress);
            }
        }

        // Generate a link to create an event on Google calendar
        if ($calendarName == "google") {
            return $link->google();
        } elseif ($calendarName == "yahoo") {
            // Generate a link to create an event on Yahoo calendar
            return $link->yahoo();
        } elseif ($calendarName == "weboutlook") {
            // Generate a link to create an event on outlook.com calendar
            return $link->webOutlook();
        } else {
            // Generate a data uri for an ics file (for iCal & Outlook)
            return $link->ics();
        }
    }

    public function showEventHomePreview($event_id)
    {
        // return showEventHome($event_id, true);
    }

    /**
     * Sends a message to the organiser
     *
     * @param Request $request
     * @param $event_id
     * @return mixed
     */
    public function postContactOrganiser(Request $request, $event_id)
    {
        $rules = [
            'name'    => 'required',
            'email'   => ['required', 'email'],
            'message' => ['required'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'   => 'error',
                'messages' => $validator->messages()->toArray(),
            ]);
        }

        $event = Event::findOrFail($event_id);

        $data = [
            'sender_name'     => $request->get('name'),
            'sender_email'    => $request->get('email'),
            'message_content' => strip_tags($request->get('message')),
            'event'           => $event,
        ];

        Mail::send('Emails.messageReceived', $data, function ($message) use ($event, $data) {
            $message->to($event->organiser->email, $event->organiser->name)
                ->from(config('attendize.outgoing_email_noreply'), $data['sender_name'])
                ->replyTo($data['sender_email'], $data['sender_name'])
                ->subject(trans("Email.message_regarding_event", ["event" => $event->title]));
        });

        return response()->json([
            'status'  => 'success',
            'message' => trans("Controllers.message_successfully_sent"),
        ]);
    }

    public function showCalendarIcs(Request $request, $event_id)
    {
        $event = Event::findOrFail($event_id);

        $icsContent = $event->getIcsForEvent();

        return response()->make($icsContent, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="event.ics'
        ]);
    }

    /**
     * @param Request $request
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function postShowHiddenTickets(Request $request, $event_id)
    {
        $event = Event::findOrFail($event_id);

        $accessCode = strtoupper(strip_tags($request->get('access_code')));
        if (!$accessCode) {
            return response()->json([
                'status' => 'error',
                'message' => trans('AccessCodes.valid_code_required'),
            ]);
        }

        $unlockedHiddenTickets = $event->tickets()
            ->where('is_hidden', true)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->filter(function ($ticket) use ($accessCode) {
                // Only return the hidden tickets that match the access code
                return ($ticket->event_access_codes()->where('code', $accessCode)->get()->count() > 0);
            });

        if ($unlockedHiddenTickets->count() === 0) {
            return response()->json([
                'status' => 'error',
                'message' => trans('AccessCodes.no_tickets_matched'),
            ]);
        }

        // Bump usage count
        EventAccessCodes::logUsage($event_id, $accessCode);

        return view('Public.ViewEvent.Partials.EventHiddenTicketsSelection', [
            'event' => $event,
            'tickets' => $unlockedHiddenTickets,
            'is_embedded' => 0,
        ]);
    }


    // Home page for clubs like /london, /paris, /newyork, etc.
    public function clubPage($club)
    {
        // Get the latest event for the club
        $event = Event::query()
            ->where('club', $club)
            ->where('webinar', '0')
            ->where('custome', '0')
            ->where('status', 'active')
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->first();

        if ($event == null) {

            $event = Event::query()
                ->where('club', $club)
                ->where('webinar', '0')
                ->where('status', 'active')
                ->orderBy('start_date', 'desc')
                ->first();
        }

        if (isset($event->id)) {
            return $this->showEvent($event->id);
        }

        $login = 0;

        if (session('access_token')) {
            $login = 1;
        }

        $data = array(
            'eventsData' => [],
            'loginData' => $login,
            'club' => $club,
            'firstname' => '',
            'lastname' => '',
            'email' => '',
            'avatar' => '',
            'admin' => 0
        );

        return view('Public.Home.clubevents')->with($data);
    }

    public function showEvent($id)
    {
        if ($id == 3258) {
            //echo "This event is now sold out.";
            //exit;

        }

        $multiDate = false;

        if ($id == 3456) {
            $multiDate = true;
        }

        session([
            'has_unique_link_access' => false,
            'percent_off' => null,
            'couponCode' => null,
            'discountAmount' => null,
            'is_hybrid' => null,
            'referal_email' => null,
            'is_allowed_attend_event' => true
        ]);

        // Check if we have $request->inlnk and check if its a valid link and not expired yet.
        if (request('inlnk')) {
            $uniqueLink = LoungeEventUniqueLink::where('token', request('inlnk'))->first();

            if ($uniqueLink && !$uniqueLink->isExpired()) {
                // We can set a session variable to allow access to the event
                session(['has_unique_link_access' => true]);

                if ($uniqueLink->discount_type && $uniqueLink->discount_value) {
                    // If the unique link has discount info then we can save it in session to apply during checkout
                    if (in_array($uniqueLink->discount_type, ['percent', 'percentage'], true)) {
                        session(['percent_off' => $uniqueLink->discount_value]);
                    } elseif ($uniqueLink->discount_type == 'amount') {
                        session(['discountAmount' => $uniqueLink->discount_value]);
                    }

                    // Dont show the modal since its not a free
                    session(['has_unique_link_access' => false]);
                }
            } else {
                return view('Public.ViewEvent.InvalidUniqueLink');
            }
        }

        store_club_commission(request('c'));

        // session(['referal_email_accepted' => null]);

        // When user purchase with referal code we save the referal email in session
        if (request('ref')) {
            $refCode = request('ref');
            // URL-safe base64 encode. To get email back: base64_decode(strtr($refCode, '-_', '+/'))
            $referalEmail = base64_decode(strtr($refCode, '-_', '+/'));

            // Check if the email is actually a member
            if (is_member($referalEmail) == '1') {
                session(['referal_email' => $referalEmail]);
                session(['referal_first_name' => session('referal_first_name')]);
                session(['referal_last_name' => session('referal_last_name')]);
            }
        }

        // Check if id is a number or we can use the slug
        if (!is_numeric($id)) {
            $slug = $id;
            $eventBySlug = Event::where('slug', $slug)->first();
            if ($eventBySlug) {
                $id = $eventBySlug->id;
            }
        }
        $email = session('email') ?? 'test@gmail.com';
        // Call API then identify if the subscription is Expired or Active status
        session(['member' => is_member($email)]);

        $login = '0';

        if (request('member-event')) {
            $eventExists = Event::where('lobby_event_id', $id)->first();
        } else {
            $eventExists = Event::find($id);
        }

        $memberEvent = false;

        if ($eventExists) {
            if ($eventExists->is_member == true) {
                $memberEvent = true;
            }

            if ($eventExists->lobby_event_id == null) {
            }
        }

        // If its a member event then show other view
        if (request('member-event') || $memberEvent == true) {
            return $this->memberEvent($id, $login);
        }

        session()->put('member-event', false);

        $login = "0";
        $event_id1 = '';
        $eventIdUrl = $id;
        $club = "accra";
        $oldEvent = "0";

        $avatar = session('avatar') ?? '';
        $firstname = session('firstname') ?? '';
        $lastname = session('lastname') ?? '';
        $email = session('email') ?? '';
        $member = is_member($email);
        $phone_number = session('phone') ?? '';

        $event_id1 = $eventIdUrl;

        $canBookRight = false;
        $isBringFriend = false;

        // In this page we get the video from admin_members.clubs table
        $eventsData = Event::where('id', $id)->limit(1)->get();
        if (count($eventsData) > 0) {
            $club = $eventsData[0]->club;
            $isBringFriend = (bool) $eventsData[0]->is_bring_friend;
            // This could be the discount if enabled
            $isBringFriendPercentage = $eventsData[0]->is_bring_friend_percentage;
            $canBookRight = (bool) $eventsData[0]->can_book_right;

            $videoUrl = '';
            $clubVideo = LoungeClub::where('slug', $eventsData[0]->club)->first();
            if ($clubVideo) {
                $videoUrl = $clubVideo->video_url;
            }

            // Check if the events table itself has a video to show
            if ($eventsData[0]->event_video != null) {
                // Video URL should be youtube embed or video URL itself
                $videoUrl = $eventsData[0]->event_video;
            }

            $cClub = ucfirst($club);
            $cClub1 = ucfirst($club);
            if (session('access_token')) {
                $login = "1";

                $visit = VisitLog::firstOrNew([
                    'email_id' => $email,
                    'event_id' => $event_id1,
                ]);

                if ($visit->date_created == null) {
                    $visit->date_created = now()->format('Y-m-d');
                }

                $visit->first_name = $firstname;
                $visit->last_name = $lastname;
                $visit->app_user_type = session('app_user_type') ?? null;
                $visit->is_attended_event = session('is_attended_event') ?? false;
                $visit->created = now();
                $visit->club = $club;
                $visit->mobile_no = $phone_number;
                $visit->save();

                // TODO we could just put it on queue
                // Insert to GHL with events details as tagss
                $eventDateTag = $club . '-' .  str_replace('-', '', substr($eventsData[0]->start_date, 0, 10));
                $ghl = new GoHighLevel();
                $locationId = $ghl->locationId;
                $contact = $ghl->createUpdateContact([
                    'firstName' => $firstname,
                    'lastName' => $lastname,
                    'email' => $email,
                    'locationId' => $locationId,
                ]);

                if (isset($contact['contact']['id'])) {
                    $ghl->addTags($contact['contact']['id'], ['eventvisits', $eventDateTag]);
                    // Add member visits
                    if ($member == '1') {
                        $ghlMember = 'member';
                    } else {
                        $ghlMember = 'nonmember';
                    }
                    $ghl->addTags($contact['contact']['id'], [$eventDateTag . '-visits-' . $ghlMember]);

                    // Visits here
                    $webhookJob = new \App\Jobs\HighLevelEventWebhook(
                        $eventsData[0],
                        $email,
                        $club,
                        substr($eventsData[0]->start_date, 0, 10),
                        $eventsData[0]->title,
                        'visited'
                    );
                    dispatch($webhookJob);
                }
            } else {

                $cityName = 'City';
                $regionName = 'State';
                $countryName = 'Country';
                $zipCode = 'zip';
                if ($position = Location::get()) {
                    // Successfully retrieved position.
                    $cityName = $position->cityName;
                    $regionName = $position?->regionName;
                    $countryName = $position->countryName;
                    $zipCode = $position?->zipCode;
                }
                // For non logged in users
                $visit = VisitLog::firstOrNew([
                    'email_id' => request()->ip(),
                    'event_id' => $event_id1
                ]);

                $visit->first_name = $cityName . ', ' . $regionName . ', ' . $zipCode;
                $visit->last_name = $countryName ?? '.';
                $visit->created = now();
                $visit->is_ipaddress = true;
                $visit->club = $club;

                if ($visit->date_created == null) {
                    $visit->date_created = now()->format('Y-m-d');
                }

                $visit->save();
            }
            session([
                'clubs' => $club,
                'eeventId' => $event_id1,
            ]);
            if ($cClub == "Orangecounty") {
                $cClub1 = "Orange-County";
            }

            if ($club == "london-mayfair") {
                $cClub = 'LondonMayFair';
            }

            $data = [
                'eventsData' => $eventsData,
                'club' => $cClub1,
                'eventsVideo' => $videoUrl,
                'loginData' => $login,
                'member' => $member,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'oldEvent' => $oldEvent,
                'avatar' => $avatar,
                'phone_number' => $phone_number,
                'is_allowed_attend_event' => session('is_allowed_attend_event'),
            ];

            // Check if preview
            // This is for testing
            // https://events.test/club/1163?edit_event=1
            // TODO MAKE THIS false in PRODUCTION
            $data['can_edit'] = false;
            $data['is_admin'] = $this->isAdmin;
            $data['canBookRight'] = $canBookRight;
            $data['isBringFriend'] = $isBringFriend;

            // TODO // can set up to false so that edit will disable in production
            $enableEditFeature = true;

            if ($enableEditFeature == true) {
                $clubData = new stdClass();
                $clubData->id = null;
                $clubData->description = null;
                $clubData->about_event = null;
                $clubData->who_event = null;
                $clubData->meeting_section = null;
                $clubData->benefits = null;
                $clubData->book_call_image = null;
                $clubData->who_event_image_url = null;
                $clubData->meeting_section_image_url = null;
                $clubData->club_info_section_first_text = null;
                $clubData->club_info_section_second_text = null;
                $clubData->club_info_section_video = null;

                $clubData->showup = null;

                // Default club gallery images
                $clubData->clubGalleryImages = [
                    'https://davxegdvkjbks.cloudfront.net/club-images/305x203/paris/1.jpg',
                    'https://davxegdvkjbks.cloudfront.net/club-images/305x203/paris/2.jpg',
                    'https://davxegdvkjbks.cloudfront.net/club-images/305x203/paris/3.jpg',
                    'https://davxegdvkjbks.cloudfront.net/club-images/305x203/paris/4.jpg',
                ];

                // Default images
                $clubData->galleryImages = [
                    'https://images.clickfunnels.com/db/6c1ec551894002accee414c4d0c740/WhatsApp-Image-2023-01-10-at-6.35.14-PM.jpg',
                    'https://images.clickfunnels.com/e7/4e59bb8257406d912af83b47b926be/WhatsApp-Image-2022-01-27-at-13.19.18.jpg',
                    'https://images.clickfunnels.com/43/e29ffeb6a547b98a16d43b3b9e32da/WhatsApp-Image-2023-01-10-at-6.35.17-PM-1-.jpg',
                    'https://images.clickfunnels.com/de/29db9ebc344f91a2ffe92e80548686/WhatsApp-Image-2022-01-27-at-13.19.20.jpg',
                    'https://images.clickfunnels.com/ed/3f8ec035ee4e98a7b6e00282ec2867/WhatsApp-Image-2023-01-10-at-6.35.20-PM.jpg',
                ];

                $data['clubData'] = $clubData;
            }

            $data['edit_mode'] = false;

            if (request('edit_event') == 1) {
                $data['edit_mode'] = true;
            }

            // if (request('edit_event') && $enableEditFeature == true) {
            if ($enableEditFeature == true) {

                $data['event'] = $eventsData[0];

                // Show the editor only to whom the edit routes will let save.
                $email = $this->verifiedEditorEmail();

                if (session('access_token')) {
                    $login = 1;
                }

                // Get club data from lounge database
                $clubData = $clubs = DB::connection('mysql_lounge')->table('clubs')->where('slug', $club)->first();

                $clubId = $clubs?->id;
                // Add gallery images
                $galleryImages = DB::connection('mysql_lounge')
                    ->table('club_images')
                    ->where('club_id', $clubId)
                    ->where('is_gallery_section', true)
                    ->get();

                if ($galleryImages->count() > 0) {
                    $clubData->galleryImages = $galleryImages;
                }

                // Add club gallery images
                $clubGalleryImages = DB::connection('mysql_lounge')
                    ->table('club_images')
                    ->where('club_id', $clubId)
                    ->where('is_gallery_section', false)
                    ->get();

                if ($clubGalleryImages->count() > 0) {
                    $clubData->clubGalleryImages = $clubGalleryImages;
                }

                $data['clubData'] = $clubData;

                $data['sections'] = [
                    'QuoteSection' => true,
                    'SectionTwo' => true, //  contains book your ticket here
                    'About1Section' => true, // Who Is This Event For?
                    'NetworkingEvents' => true, // Our Events Are Popular Around The World! and some images
                    'LobbySection' => true,
                    'MirelaSection' => true,
                    'MeetingSection' => true,
                    'BookCallSection' => true, // Book Your Free Discovery Session Call!
                    'HostInfoSection' => true,
                    'ClubInfoSection' => true,
                    'ClubGallerySection' => true,
                    'BannerSection-2' => true,

                    'GalleryCardSection' => true,
                    'BannerSection-3' => true,
                    'BenefitsSection' => true,
                    'BannerSection' => true,
                    'TestimonialsSection' => true,
                    'SectionThree' => true,
                    'MemberSection' => true,
                    'GallerySection' => true,
                ];

                if ($this->canEdit($email, $club)) {
                    $data['can_edit'] = true;
                    $data['is_admin'] = $this->isAdmin;
                    // return view('Public.ClubEvents.Default.Default')->with($data);
                }
            }

            if ($clubData != null) {
                $clubData->right_place_text = null;
            }

            if ($eventsData->count() > 0) {
                $event = $eventsData[0];
                if ($clubData != null) {
                    if ($event->section_two_description != null) {
                        $clubData->description = $event->section_two_description;
                    }

                    if ($event->about_event != null) {
                        $clubData->about_event = $event->about_event;
                    }

                    if ($event->who_event != null) {
                        $clubData->who_event = $event->who_event;
                    }

                    if ($event->right_place_text != null) {
                        $clubData->right_place_text = $event->right_place_text;
                    }

                    if ($event->showup != null) {
                        $clubData->showup = $event->showup;
                    }
                }

                $data['clubData'] = $clubData;
            }

            if (is_default_template($club)) {
                if ($club == 'globalmanclub') {
                    $data['globalmanclub'] = true;
                    $data['bgColorClass'] = 'bg-dark';
                    $data['bgColorClass'] = ' ';
                    $data['bgColorNeonClass'] = 'bg-neon';
                    $data['textColorClass'] = 'text-neon';
                    // $data['can_edit'] = true;
                }
                // Check if the event template is summit
                if ($event->is_summit_template == true) {
                    $data['videoTitle'] = 'Global Woman Summit';
                    $data['ticketText'] = $event?->title ?? 'Global Woman Summit';
                    $data['multiDate'] = $multiDate;
                    return view('Public.ClubEvents.Default.Summit')->with($data);
                } 

                return view('Public.ClubEvents.Default.Default')->with($data);
            }

            try {
                return view('Public.ClubEvents.' . $cClub . '.' . $cClub . '')->with($data);
            } catch (Exception $e) {

                Log::error('Unable to find view for club: ' . $cClub . '. Error: ' . $e->getMessage());
                return view('Public.ClubEvents.Default.Default')->with($data);
            }

            return view('Public.ClubEvents.' . $cClub . '.' . $cClub . '')->with($data);
        } else {
            Session::forget('event_id1');
            return view('Public.ClubEvents.noevents');
        }
    }

    public function memberEvent($id, $login)
    {
        $email = session('email') ?? 'test@gmail.com';
        // Call API then identify if the subscription is Expired or Active status

        $eventDetails = [];

        $lobbyEventId = $id;

        session([
            'member' => is_member($email),
            'eeventId' => $lobbyEventId,
        ]);

        session()->put('member-event', true);

        if (session('access_token')) {
            $login = "1";
        }

        $extraDescription = '';
        $eventsVideo = 'https://www.youtube.com/embed/51AnJ7vfQtc?si=Djw93uADedxpLa0R';
        // $eventsVideo = 'https://www.youtube.com/watch?v=yrATC7498k4';
        $eventsVideo = 'https://www.youtube.com/embed/yrATC7498k4?si=0tOo7bFChkzGXEeK';

        if (request('member-event')) {
            $client = new \GuzzleHttp\Client();

            // Check if exists
            $request = $client->request(
                "GET",
                'https://plbbr86fcj.execute-api.eu-west-2.amazonaws.com/prod/geteventdetail?userId=1&eventId=' . $lobbyEventId
            );

            $response = $request->getBody();

            $json = json_decode($response, true);

            if (isset($json['data'])) {
                $eventDetails = $json['data'][0];

                $eventDetails = (object)$eventDetails;

                $eventDetails->title = $eventDetails->postTitle;
                $eventDetails->event_timezone = $eventDetails->timezone;
                $eventDetails->location = $eventDetails->postLocation;

                // Lobby event id =$eventDetails->postId
                // Create event in events database if not exists
                $event = Event::where('lobby_event_id', $eventDetails->postId)->first();

                // This is not a premium member event and always free
                if ($event == null) {
                    $event = Event::latest()->first();
                    $newEvent = $event->replicate();

                    $newEvent->title = $eventDetails->title;
                    $newEvent->location = $eventDetails->postLocation;
                    $newEvent->description = $eventDetails->postDescription;
                    $newEvent->organiser_fee_fixed = 0;
                    $newEvent->organiser_fee_percentage = 0;
                    // $newEvent->venue = $eventDetails->location;
                    // $newEvent->lobby_event_id = $eventDetails->postId;
                    $newEvent->lobby_event_id = null;
                    $newEvent->event_timezone = $eventDetails->event_timezone;
                    $newEvent->club = null;
                    $newEvent->event_ttype = $eventDetails->etype;
                    $newEvent->mprice = 0;
                    $newEvent->nmprice = 0;
                    $newEvent->save();

                    // TODO Create ticket here
                    DB::table('tickets')->insert([
                        'event_id' => $newEvent->id,
                        'title' => 'Member Event',
                        'quantity_available' => 500,
                        'start_sale_date' => date("Y-m-d H:i"),
                        'created_at' => date("Y-m-d H:i"),
                        'updated_at' => date("Y-m-d H:i"),
                        'end_sale_date' => null,
                        'price' => 0,
                        'min_per_person' => 1,
                        'max_per_person' => 30,
                        'description' => '',
                        'account_id' => 14,
                        'user_id' => 31,
                        'is_hidden' => 0
                    ]);
                }

                $eventDetails->id = '0';
                if ($event) {
                    $eventDetails->id = $event->id;
                }

                $eventDetails->event_ttype = $eventDetails->etype;
                $eventDetails->lobby_event_id = $eventDetails->postId;

                $eventDetails->organiser_fee_percentage = $eventDetails->vatId;

                // If not premium then its always free
                if ($event->is_member == false) {
                    $eventDetails->organiser_fee_percentage = '0';
                    $eventDetails->mprice = 0;
                    $eventDetails->nmprice = 0;
                }
            }
        } else {

            $event = Event::find($id);

            if ($event) {
                $client = new \GuzzleHttp\Client();

                // Check if exists
                $request = $client->request(
                    "GET",
                    'https://plbbr86fcj.execute-api.eu-west-2.amazonaws.com/prod/geteventdetail?userId=1&eventId=' . $event->lobby_event_id
                );

                $response = $request->getBody();

                $json = json_decode($response, true);

                if (isset($json['data'])) {
                    $eventDetails = $json['data'][0];

                    $eventDetails = (object)$eventDetails;
                    $eventDetails->title = $eventDetails->postTitle;
                    $eventDetails->event_timezone = $eventDetails->timezone;
                    $eventDetails->location = $eventDetails->postLocation;
                    $eventDetails->id = $event->id;
                    $eventDetails->event_ttype = $eventDetails->etype;
                    $eventDetails->lobby_event_id = $eventDetails->postId;
                    $eventDetails->organiser_fee_percentage = $eventDetails->vatId;
                }

                $extraDescription = $event->extra_description;
                $eventsVideo = $event->event_video;
            }
        }

        $avatar = session('avatar') ?? '';
        $firstname = session('firstname') ?? '';
        $lastname = session('lastname') ?? '';
        $email = session('email') ?? 'test@gmail.com';
        // Call API then identify if the subscription is Expired or Active status
        session([
            'member' => is_member($email),
        ]);
        $member = session('member') ?? '0';
        $phone_number = session('phone') ?? '';

        // Add event visits logs
        if (session('access_token')) {
            $login = "1";
            // DB::table('Wo_Clickfunnel_Event_visit_logs')->insert(
            //     ['email_id' => $email, 'first_name' => $firstname, 'last_name' => $lastname, 'created' => now(), 'club' => null, 'event_id' => $id]
            // );

            $visit = VisitLog::firstOrNew([
                'email_id' => $email,
                'event_id' => $id
            ]);

            if ($visit->date_created == null) {
                $visit->date_created = now()->format('Y-m-d');
            }

            $visit->first_name = $firstname;
            $visit->last_name = $lastname;
            $visit->created = now();
            $visit->mobile_no = $phone_number;
            $visit->club = null;
            $visit->save();
        }

        // No data found
        if (is_array($eventDetails)) {
            Session::forget('event_id1');
            return view('Public.ClubEvents.noevents');
        }

        return view('Public.MemberEvents.Member.Default', [
            'event' => $eventDetails,
            'email' => $email,
            'avatar' => $avatar,
            'club' => '',
            'eventsVideo' => $eventsVideo,
            'extraDescription' => $extraDescription,
            'loginData' => $login,
            'member' => $member,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'oldEvent' => 0,
            'phone_number' => $phone_number,
            'isMemberEvent' => true,
        ]);
    }

    public function getClubDetails($club)
    {
        $clubData = new stdClass();
        $clubData->id = null;
        $clubData->description = null;
        $clubData->about_event = null;
        $clubData->who_event = null;
        $clubData->meeting_section = null;
        $clubData->benefits = null;
        $clubData->book_call_image = null;
        $clubData->who_event_image_url = null;
        $clubData->meeting_section_image_url = null;
        $clubData->club_info_section_first_text = null;
        $clubData->club_info_section_second_text = null;
        $clubData->club_info_section_video = null;
        $clubData->remember_join_image = null;
        $clubData->remember_join = null;
        $clubData->message_mirela_image = null;
        $clubData->message_mirela = null;
        $clubData->showup = null;
    }

    // TODO
    // Make sure that this is false in production
    public $isAdmin = false;

    public function canEdit($email, $club)
    {
        // return true;
        $isAdmin = false;

        if ($email != null) {
            // Check if the email is director or admin
            // Check the email in admin_members.lobbyusers(lounge) table
            $lobbyUser = DB::connection('mysql_lounge')->table('lobbyusers')->where('email', $email)->first();

            if ($lobbyUser) {

                $userId = $lobbyUser->user_id;

                // Check the role of the user_id
                $userRole = DB::connection('mysql_lounge')->table('user_roles')->where('user_id', $userId)->first();
                $userRoles = DB::connection('mysql_lounge')->table('user_roles')->where('user_id', $userId)->pluck('role_id');

                if ($userRole) {
                    if ($userRole->role_id == 1 || $userRole->role_id == 2 || $userRole->role_id == 3) {
                        $isAdmin = true;
                        $this->isAdmin = $isAdmin;
                    }
                }

                // TODO
                if ($userRoles) {

                    foreach ($userRoles->toArray() as $roleId) {
                        if ($roleId == 1 || $roleId == 2) {
                            $isAdmin = true;
                            $this->isAdmin = $isAdmin;
                        }
                    }
                }

                // If director of the club or admin
                if (($lobbyUser->director == 1 && $lobbyUser->club == $club) || $isAdmin == true) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The content_field values the club-page editor sends: the data-content-field attributes in
     * resources/views/Public/ClubEvents and resources/views/Public/MemberEvents.
     * updateClubTemplate() uses the value as a column name on the Lounge clubs row and as an
     * attribute on the event, so nothing else may reach it (no stripe_*, *_id, slug, email,
     * commission*, is_* or id).
     */
    private const EDITABLE_CONTENT_FIELDS = [
        'about_event',
        'about_regional_director',
        'after_ticket_image',
        'banner_section_1',
        'banner_section_2',
        'banner_section_3',
        'benefits',
        'book_call_image',
        'club_info_section_first_text',
        'club_info_section_second_text',
        'club_info_section_video',
        'description',
        'director_image',
        'event_video',
        'global_woman_partnership',
        'globalman_exists_text',
        'meeting_section',
        'message_mirela',
        'quote_text',
        'remember_join',
        'right_place_text',
        'showup',
        'who_event',
    ];

    /** magazine_section_{Lounge event_sections.id}, from resources/views/livewire/magazine-section.blade.php. */
    private const MAGAZINE_SECTION_FIELD = '/^magazine_section_([1-9][0-9]{0,9})$/';

    private const MAX_GALLERY_FILES = 20;

    private const MAX_CONTENT_FILES = 10;

    private const MAX_IMAGE_KILOBYTES = 10240;

    /** Seconds. The mobile number Lambda has not been timed, so these are judgements. */
    private const UPSTREAM_CONNECT_TIMEOUT = 2;

    private const UPSTREAM_TIMEOUT = 10;

    /**
     * The email of a Lobby sign-in completed in this session, or null.
     *
     * session('email') is not an identity: public routes (POST saveUserData, the booking flow)
     * set it from whatever the visitor typed. 'events_email' is written only once the Lobby has
     * returned the signed-in user (UserLoginController::callback and __construct()).
     */
    protected function verifiedEditorEmail(): ?string
    {
        $email = session('events_email');

        return is_string($email) && $email !== '' ? $email : null;
    }

    /**
     * Stops the request with a 403 unless the signed-in Lobby user directs $clubSlug or is an admin.
     */
    protected function authorizeClubEdit($clubSlug): void
    {
        $email = $this->verifiedEditorEmail();

        if ($email === null || !is_string($clubSlug) || $clubSlug === '' || !$this->canEdit($email, $clubSlug)) {
            abort(403);
        }
    }

    private function positiveId($value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    /**
     * updateClubTemplate() writes to the club named by club_id, the club of event_id and the club
     * of a magazine section's event. All of them must be one club, and the user must be able to
     * edit it; content_field must be on the allow-list.
     */
    protected function authorizeClubTemplateUpdate(Request $request): void
    {
        if ($this->verifiedEditorEmail() === null) {
            abort(403);
        }

        $field = $request->input('content_field');
        $isGallery = $request->input('multiple_images_gallery') == 1;

        if ($field !== null && $field !== '') {
            if (!is_string($field)
                || !(in_array($field, self::EDITABLE_CONTENT_FIELDS, true) || preg_match(self::MAGAZINE_SECTION_FIELD, $field))) {
                abort(422, 'This field cannot be edited.');
            }
        } elseif (!$isGallery) {
            abort(422, 'This field cannot be edited.');
        }

        // In the order canEdit() should see them: showEvent() checks the event's club.
        $slugs = [];

        $eventId = $request->input('event_id');
        if ($eventId !== null && $eventId !== '') {
            $event = ($id = $this->positiveId($eventId)) ? Event::find($id) : null;
            if (!$event) {
                abort(403);
            }
            $slugs[] = $event->club;
        }

        if (is_string($field) && preg_match(self::MAGAZINE_SECTION_FIELD, $field, $match)) {
            $section = LoungeEventSection::find((int) $match[1]);
            $sectionEvent = $section ? Event::find($section->event_id) : null;
            if (!$sectionEvent) {
                abort(403);
            }
            $slugs[] = $sectionEvent->club;
        }

        $clubId = $request->input('club_id');
        if ($clubId !== null && $clubId !== '') {
            $club = ($id = $this->positiveId($clubId))
                ? DB::connection('mysql_lounge')->table('clubs')->where('id', $id)->first()
                : null;
            if (!$club) {
                abort(403);
            }
            $slugs[] = $club->slug;
        }

        $distinct = array_unique(array_map(fn ($slug) => strtolower(trim((string) $slug)), $slugs));

        if (count($distinct) !== 1) {
            abort(403);
        }

        $this->authorizeClubEdit($slugs[0]);
    }

    public function updateClubTemplate(Request $request)
    {
        $this->authorizeClubTemplateUpdate($request);

        if ($request->input('multiple_images_gallery') == 1) {
            $request->validate([
                'gallery_files' => ['nullable', 'array', 'max:' . self::MAX_GALLERY_FILES],
                'gallery_files.*' => ['image', 'max:' . self::MAX_IMAGE_KILOBYTES],
            ]);
        }

        if ($request->hasFile('file_upload')) {
            $request->validate([
                'file_upload' => ['image', 'max:' . self::MAX_IMAGE_KILOBYTES],
            ]);
        }

        if (request('multiple_images_gallery') == 1) {

            $targetAction = request('target_section', 'gallery');

            if ($targetAction == 'gallery') {
                $isGallerySection = true;
                $redirectTo = request()->root() . '/club/' . $request->event_id . '?edit_event=1#section-gallery-images';
            } else {
                // club-gallery
                $isGallerySection = false;
                $redirectTo = request()->root() . '/club/' . $request->event_id . '?edit_event=1#section-club-gallery-images';
            }

            $event = Event::find($request->event_id);

            if ($event) {

                $event->club;

                $clubData = DB::connection('mysql_lounge')->table('clubs')->where('slug', $event->club)->first();

                if ($clubData) {

                    if ($request->file('gallery_files') == null) {
                        // Redirect back to multiple images upload
                        return redirect()->to($redirectTo);
                    }

                    // $images = [];
                    foreach ($request->file('gallery_files') as $fileUpload) {
                        $path = $fileUpload->store(
                            'Events Images',
                            's3'
                        );

                        $imageUrl = Storage::disk('s3')->url($path);

                        // Insert to images
                        DB::connection('mysql_lounge')->table('club_images')->insert([
                            'is_gallery_section' => $isGallerySection,
                            'club_id' => $clubData->id,
                            'image_url' => $imageUrl
                        ]);

                        // $images[] = $imageUrl;
                    }

                    // Redirect back to multiple images upload
                    return redirect()->to($redirectTo);
                }
            }
        }

        $imageUrl = null;

        if ($request->file('file_upload') != null && $request->file('file_upload') != 'undefined') {

            // Check if content_field contains magazine_section_
            if (str_contains($request->content_field, 'magazine_section_')) {

                // Validate and get the uploaded file
                $file = $request->file('file_upload');

                // Create an Intervention Image instance (auto-detect driver)
                $image = ImageManager::gd()->read($file->getPathname());

                // Resize the image (e.g., max width = 625px, keep aspect ratio)
                // $image->resize(625, null, function ($constraint) {
                //     $constraint->aspectRatio();
                //     $constraint->upsize();
                // });

                // cover a size of 600x400 and position crop on the left
                $image->cover(600, 400, 'left'); // 300 x 300 px

                // Encode the image to the desired format (e.g., jpg, png)
                $resizedImage = $image->encode();

                // Generate a unique filename
                $filename = 'Events Images/' . uniqid(). $file->getClientOriginalExtension();

                // Upload to S3 using the Storage facade
                Storage::disk('s3')->put($filename, (string) $resizedImage, 'public');

                // Return the file URL or success response
                $imageUrl = Storage::disk('s3')->url($filename);
            } else {
                // We can process image sizes here.
                $path = $request->file('file_upload')->store(
                    'Events Images',
                    's3'
                );

                $imageUrl = Storage::disk('s3')->url($path);
            }
        }

        // If we have file_upload file then upload to s3 and get the URL
        $clubId = $request->club_id;
        // $clubData = DB::connection('mysql_lounge')->table('clubs')->find($clubId);

        if ($request->content != null && $request->content != '') {

            // For magazine section
            // Check if content_field contains magazine_section_
            if (str_contains($request->content_field, 'magazine_section_')) {

                // 1, 2, 3, 4, 5
                $magazineSectionNumber = str_replace('magazine_section_', '', $request->content_field);
                // Update the event table magazine_section_{n}
                $loungeEventSection = LoungeEventSection::find($magazineSectionNumber);

                if (!$loungeEventSection) {
                    $loungeEventSection = new LoungeEventSection();
                }

                if ($imageUrl != null) {
                    $loungeEventSection->section_image = $imageUrl;
                }

                $loungeEventSection->section_content = $request->content;
                $loungeEventSection->is_placeholder = false;
                $loungeEventSection->save();

                return redirect()->back();
            }

            if ($request->content_field != 'right_place_text') {
                DB::connection('mysql_lounge')->table('clubs')
                    ->where('id', $clubId)
                    ->update([$request->content_field => $request->content ?? request('image_url')]);
            }

            $event = Event::find(request('event_id'));

            if ($event) {
                if ($request->content_field == 'description') {
                    $event->section_two_description = $request->content;
                } else {
                    $event->{$request->content_field} = $request->content;
                }

                try {
                    $event->save();
                } catch (Exception $e) {
                    Log::info($e->getMessage());
                }
            }
        }

        if ($imageUrl != null) {

            if ($request->content_field == 'who_event') {
                DB::connection('mysql_lounge')->table('clubs')
                    ->where('id', $clubId)
                    ->update(['who_event_image_url' => $imageUrl]);
            }

            if ($request->content_field == 'meeting_section') {
                DB::connection('mysql_lounge')->table('clubs')
                    ->where('id', $clubId)
                    ->update(['meeting_section_image_url' => $imageUrl]);
            }

            if ($request->content_field == 'book_call_image') {
                DB::connection('mysql_lounge')->table('clubs')
                    ->where('id', $clubId)
                    ->update(['book_call_image' => $imageUrl]);
            }

            if ($request->content_field == 'director_image') {
                DB::connection('mysql_lounge')->table('clubs')
                    ->where('id', $clubId)
                    ->update(['director_image' => $imageUrl]);
            }

            if ($request->content_field == 'after_ticket_image') {
                DB::connection('mysql_lounge')->table('clubs')
                    ->where('id', $clubId)
                    ->update(['after_ticket_image' => $imageUrl]);
            }

            if ($request->content_field == 'message_mirela') {
                DB::connection('mysql_lounge')->table('clubs')
                    ->where('id', $clubId)
                    ->update(['message_mirela_image' => $imageUrl]);
            }

            if ($request->content_field == 'remember_join') {
                DB::connection('mysql_lounge')->table('clubs')
                    ->where('id', $clubId)
                    ->update(['remember_join_image' => $imageUrl]);
            }

            if ($request->content_field == 'banner_section_1') {
                DB::connection('mysql_lounge')->table('clubs')
                    ->where('id', $clubId)
                    ->update(['banner_section_1' => $imageUrl]);
            }

            if ($request->content_field == 'banner_section_2') {
                DB::connection('mysql_lounge')->table('clubs')
                    ->where('id', $clubId)
                    ->update(['banner_section_2' => $imageUrl]);
            }

            if ($request->content_field == 'banner_section_3') {
                DB::connection('mysql_lounge')->table('clubs')
                    ->where('id', $clubId)
                    ->update(['banner_section_3' => $imageUrl]);
            }

            if ($request->content_field == 'showup') {
                DB::connection('mysql_lounge')->table('clubs')
                    ->where('id', $clubId)
                    ->update(['showup_image' => $imageUrl]);
            }

            if ($request->content_field == 'global_woman_partnership') {
                DB::connection('mysql_lounge')->table('clubs')
                    ->where('id', $clubId)
                    ->update(['global_woman_partnership_image' => $imageUrl]);
            }
        }

        if (request('video_url') != null) {

            if ($request->content_field == 'club_info_section_video') {
                DB::connection('mysql_lounge')->table('clubs')
                    ->where('id', $clubId)
                    ->update(['club_info_section_video' => convert_youtube_video(request('video_url'))]);
            }

            // For chaning event video itself
            if ($request->content_field == 'event_video') {

                $event = Event::find(request('event_id'));

                if ($event) {
                    // Update the event video
                    if (request('video_url')) {
                        if (request('video_url') != null) {
                            $event->event_video = convert_youtube_video(request('video_url'));

                            // Need to update the club video also
                            LoungeClub::where('slug', $event->club)
                                ->update(['video_url' => $event->event_video]);
                        }
                    }

                    $event->save();
                }
            }
        }

        return redirect()->back();
    }

    public function getClubContent(Request $request, $club)
    {
        $clubData = DB::connection('mysql_lounge')->table('clubs')->where('slug', $club)->first();

        if ($clubData) {

            if (isset($clubData->content)) {
                return $clubData->content;
            }
        }

        return $request->all();
    }

    // ALTER TABLE `clubs` ADD `content` LONGTEXT NULL AFTER `commission_percentage`;

    public function updateClubContent(Request $request, $club)
    {
        $this->authorizeClubEdit($club);

        // getClubContent() serves this publicly, so the session's CSRF token must never be stored.
        $content = $request->except('_token');

        $clubData = DB::connection('mysql_lounge')->table('clubs')->where('slug', $club)->first();

        if ($clubData) {

            DB::connection('mysql_lounge')->table('clubs')
                ->where('slug', $club)
                ->update(['content' => json_encode($content)]);
        }
        return $content;
    }

    public function updateClubContentFiles(Request $request, $club)
    {
        $this->authorizeClubEdit($club);

        $request->validate([
            'uploadfiles' => ['required', 'array', 'max:' . self::MAX_CONTENT_FILES],
            'uploadfiles.*' => ['image', 'max:' . self::MAX_IMAGE_KILOBYTES],
        ]);

        $files = $request->uploadfiles;

        foreach ($files as $file) {
            $file->store('avatars'); // locally first
        }

        // It returned $request->all(), which cannot be JSON-encoded once it holds files (a 500).
        return response()->json(['stored' => count($files)]);
    }

    public function deleteImage(Request $request)
    {
        if ($this->verifiedEditorEmail() === null) {
            abort(403);
        }

        $id = $this->positiveId($request->input('id'));
        $image = $id ? DB::connection('mysql_lounge')->table('club_images')->where('id', $id)->first() : null;

        if (!$image) {
            abort(404);
        }

        $club = DB::connection('mysql_lounge')->table('clubs')->where('id', $image->club_id)->first();

        $this->authorizeClubEdit($club?->slug);

        DB::connection('mysql_lounge')->table('club_images')->where('id', $image->id)->delete();

        return response()->json(['deleted' => true]);
    }

    public function postBuyMobileUpdate(Request $request)
    {
        $telNumber = $request->input('telNumber');
        $lobbyIdUser = $request->input('lobbyIdUser');
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://xhled2r3s0.execute-api.eu-west-2.amazonaws.com/prod/mobilenumber?userId=' . $lobbyIdUser . '&mobileNumber=' . $telNumber,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => self::UPSTREAM_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::UPSTREAM_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        if ($response === false) {
            Log::warning('curl call failed', ['call' => 'mobilenumber', 'curl_errno' => curl_errno($curl)]);
        }

        curl_close($curl);
        $json = json_decode($response, true);
        $apiStatus = $json['status'] ?? null;
        if ($apiStatus == 200) {
            session([
                'phone' => $telNumber,
            ]);
            return response()->json(['status' => "success"]);
        } else {
            return response()->json(['status' => "fail"]);
        }
    }

    public function postBuyCouponCodeForm(Request $request)
    {
        $couponCodeName = $request->input('couponCodeName');
        $couponCodeEmail = $request->input('couponCodeEmail');
        $couponCodeTel = $request->input('couponCodeTel');
        $lobbyIdUser = $request->input('lobbyIdUser');
        $visitEventId = $request->input('event_id');
        $currentTime = Carbon::now();
        DB::table('Wo_Clickfunnel_Event_Guest')->insert(
            [
                'email_id' => $couponCodeEmail,
                'full_name' => $couponCodeName,
                'mobile_no' => $couponCodeTel,
                'created' => $currentTime,
                'club' => $lobbyIdUser,
                'event_id' => $visitEventId
            ]
        );

        return response()->json(['status' => "success"]);
    }

    public function saveSpeakerData(Request $request)
    {
        $user_id = '';
        if (session('ref_id')) {
            $user_id = session('ref_id');
        }
        $topic = $request->input('topic');

        $event_id = $request->input('event_id');
        $clubs = $request->input('clubs');
        $firstname = $request->input('firstname');
        $lastname = $request->input('lastname');
        $email = $request->input('email');
        $contact = $request->input('speakNumber');

        $shortDiscription = $request->input('shortDiscription');
        if ($topic == "" || $contact == "" || $shortDiscription == "") {
            return response()->json(['status' => "invalid"]);
        }
        DB::table('Wo_Clickfunnel_Event_SpeakerData')->insert(
            [
                'created' => date("Y-m-d H:i"),
                'user_id' => $user_id,
                'email_id' => $email,
                'first_name' => $firstname,
                'last_name' => $lastname,
                'topic' => $topic,
                'mobile_no' => $contact,
                'club' => $clubs,
                'event_id' => $event_id,
                'description' => $shortDiscription
            ]
        );

        return response()->json(['status' => "success"]);
    }

    public function postBuyNow(Request $request)
    {
        $eventGateway = new EventGateway();
        $eventGateway->firstName = session('firstname');
        $eventGateway->lastName = session('lastname');
        $eventGateway->email = session('email');
        $eventGateway->mobileNo = session('phone');
        $eventGateway->club = $request->input('clubs');
        $eventGateway->eventId = $request->input('event_id');
        // $eventGateway->percentOff = $request->input('percent_off');
        $eventGateway->priceData = $request->input('price_data');
        $eventGateway->purchaseEmail = $request->input('purchase_email');
        $eventGateway->isMemberEvent = $request->input('is_member_event');

        session(['is_hybrid' => $request->input('is_hybrid')]);
        session(['is_vip' => (bool)$request->input('is_vip')]);
        session(['is_bring_friend' => (bool)$request->input('is_bring_friend')]);

        // Mentors Club members are not allowed to book in-person event tickets.
        // Block early with a clear, actionable message so the frontend can
        // surface it before any reservation is created.
        $eventForMentorCheck = Event::find($request->input('event_id'));
        $mentorEmail = session('email') ?: $request->input('purchase_email');
        if ($eventForMentorCheck && $eventGateway->isInPersonBooking($eventForMentorCheck) && is_mentor($mentorEmail)) {
            return response()->json([
                'status'  => 'error',
                'message' => EventGateway::MENTOR_INPERSON_BLOCK_MESSAGE,
            ]);
        }

        $eventId = $eventGateway->bookTicket($request);

        // dd(session('ticket_order_' . $eventId));

        if ($eventId == null) {
            return response()->json([
                'status'  => 'error',
                'message' => $eventGateway->errorMessage ?? 'Event not found or no tickets available.',
            ]);
        }

        if ($eventId) {
            return response()->json([
                'status'      => 'success',
                'redirectUrl' => route('showEventPayment', [
                    'event_id'    => $eventId,
                    'is_embedded' => '0'
                ])
            ]);
        }

        abort(404, 'Event not found');

        $email2 = '';
        $first_name = session('firstname');
        $last_name = session('lastname');
        $email = session('email');
        $phone = session('phone');
        $clubs = session('clubs');
        $ref_id = session('ref_id');

        session([
            'source' => config('app.url') . '/' . $clubs,
        ]);

        $event_id = $request->input('event_id');
        $clubs = $request->input('clubs');
        $price_data = $request->input('price_data');
        $email12111 = $request->input('purchase_email');

        if ($email == "") {
            $email = $email12111;
        }
        $email2 = $email;
        $currentTime = Carbon::now();
        DB::table('Wo_Clickfunnel_Event_book_log')->insert(
            [
                'email_id' => $email,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'created' => $currentTime,
                'club' => $clubs,
                'event_id' => $event_id
            ]
        );
        $lowPrice = $price_data - 1;
        $highPrice = $price_data + 1;
        $order_expires_time = Carbon::now()->addMinutes(config('attendize.checkout_timeout_after'));
        $ticket_id  = '';
        // Event View Data

        $event = Event::findOrFail($event_id);

        // If member event then its a free event
        if ($request->is_member_event == null) {
            $ticketData = DB::select('select * from tickets where event_id = :event_id AND price>:lowPrice AND price<:highPrice', ['event_id' => $event_id, 'lowPrice' => $lowPrice, 'highPrice' => $highPrice]);
            if (empty($ticketData)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tickets Available.',
                ]);
            }
            foreach ($ticketData as $ticketall) {
                $ticket_id = $ticketall->id;
            }
            $ticket = Ticket::find($ticket_id);
        } else {
            $ticket = Ticket::where('event_id', $event_id)->first();
            $ticket_id = $ticket->id;
        }

        $i = 0;
        $order_total = 0;
        $booking_fee = 0;
        $organiser_booking_fee = 0;
        $total_ticket_quantity = 1;
        $current_ticket_quantity = 1;

        $order_total = $order_total + ($current_ticket_quantity * $ticket->price);
        $booking_fee = $booking_fee + ($current_ticket_quantity * $ticket->booking_fee);
        $organiser_booking_fee = $organiser_booking_fee + ($current_ticket_quantity * $ticket->organiser_booking_fee);


        $validation_rules = [];
        $validation_messages = [];
        $tickets = [];

        $validation_rules['ticket_holder_first_name.' . $i . '.' . $ticket_id] = ['required'];
        $validation_rules['ticket_holder_last_name.' . $i . '.' . $ticket_id] = ['required'];
        $validation_rules['ticket_holder_email.' . $i . '.' . $ticket_id] = ['required', 'email'];
        $validation_rules['ticket_holder_questions.' . $ticket_id . '.' . $i . '.' . ''] = ['required'];

        // Validate Timezone
        $validation_messages['ticket_holder_first_name.' . $i . '.' . $ticket_id . '.required'] = 'Ticket holder ' . ($i + 1) . '\'s first name is required';
        $validation_messages['ticket_holder_last_name.' . $i . '.' . $ticket_id . '.required'] = 'Ticket holder ' . ($i + 1) . '\'s last name is required';
        $validation_messages['ticket_holder_email.' . $i . '.' . $ticket_id . '.required'] = 'Ticket holder ' . ($i + 1) . '\'s email is required';
        $validation_messages['ticket_holder_email.' . $i . '.' . $ticket_id . '.email'] = 'Ticket holder ' . ($i + 1) . '\'s email appears to be invalid';
        $validation_messages['ticket_holder_questions.' . $ticket_id . '.' . $i . '.' . '' . '.required'] = "This question is required";

        // reservedTicket
        $reservedTickets = new ReservedTickets();
        $reservedTickets->ticket_id = $ticket_id;
        $reservedTickets->event_id = $event_id;
        $reservedTickets->quantity_reserved = $current_ticket_quantity;
        $reservedTickets->expires = $order_expires_time;
        $reservedTickets->session_id = session()->getId();
        $reservedTickets->save();

        $firstNameArray = array($ticket_id => $first_name);
        $lastNameArray = array($ticket_id => $last_name);
        $emailNameArray = array($ticket_id => $email);

        $tickets[] = [
            'ticket'                => $ticket,
            'qty'                   => $current_ticket_quantity,
            'price'                 => ($current_ticket_quantity * $ticket->price),
            'booking_fee'           => ($current_ticket_quantity * $ticket->booking_fee),
            'organiser_booking_fee' => ($current_ticket_quantity * $ticket->organiser_booking_fee),
            'full_price'            => $ticket->price + $ticket->total_booking_fee,
        ];
        $activeAccountPaymentGateway = $event->account->getGateway($event->account->payment_gateway_id);
        $paymentGateway = $activeAccountPaymentGateway ? $activeAccountPaymentGateway->payment_gateway : false;

        $discountAmount = 0;
        if ($request->percent_off != null && $request->percent_off != 0) {
            $discountAmount = ($request->percent_off / 100) * $order_total;

            session(['discountAmount' => $discountAmount]);
        } elseif (session('percent_off') != null && session('percent_off') != 0) {
            $discountAmount = (session('percent_off') / 100) * $order_total;

            session(['discountAmount' => $discountAmount]);
        } elseif (session('discountAmount') != null && session('discountAmount') != 0) {
            $discountAmount = min((float) session('discountAmount'), (float) $order_total);

            session(['discountAmount' => $discountAmount]);
        }

        session()->put('ticket_order_' . $event->id, [
            'validation_rules'            => $validation_rules,
            'source'            => 'events',
            'validation_messages'         => $validation_messages,
            'event_id'                    => $event->id,
            'order_first_name'          => $first_name,
            'order_last_name'           => $last_name,
            'contact_no'                => $phone,
            'ref_id'                => $ref_id,
            'business_name'             => null,
            'business_tax_number'       => null,
            'business_address_line1'    => null,
            'business_address_line2'    => null,
            'business_address_state'    => null,
            'business_address_city'     => null,
            'business_address_code'     => null,
            'is_embedded'                => null,
            'order_email'               => $email,
            'tickets'                     => $tickets,
            'total_ticket_quantity'       => $total_ticket_quantity,
            'order_started'               => time(),
            'expires'                     => $order_expires_time,
            'reserved_tickets_id'        => $reservedTickets->id,
            'discount_amount'           => $discountAmount,
            'order_total'                => $order_total - $discountAmount,
            'booking_fee'                => $booking_fee,
            'organiser_booking_fee'       => $organiser_booking_fee,
            'total_booking_fee'           => $booking_fee + $organiser_booking_fee,
            'order_requires_payment'      => PaymentUtils::requiresPayment($order_total - $discountAmount),
            'account_id'                  => $event->account->id,
            'affiliate_referral'          => Cookie::get('affiliate_' . $event_id),
            'account_payment_gateway'     => $activeAccountPaymentGateway,
            'payment_gateway'             => $paymentGateway,
            'ticket_holder_first_name'  => $firstNameArray,
            'ticket_holder_last_name'   => $lastNameArray,
            'ticket_holder_email'        => $emailNameArray,
            'final_email'     => $email2,
            'ticket_id' => $ticket_id,
            'referal_email' => session('referal_email_accepted') === true ? session('referal_email') : null,
            'referal_first_name' => session('referal_email_accepted') === true ? session('referal_first_name') : null,
            'referal_last_name' => session('referal_email_accepted') === true ? session('referal_last_name') : null,
        ]);
        // New Data

        return response()->json([
            'status'      => 'success',
            'redirectUrl' => route('showEventPayment', [
                'event_id'    => $event_id,
                'is_embedded' => '0'
            ])
        ]);
    }

    public function postBuyNow1(Request $request)
    {
        $email2 = '';

        $first_name = session('firstname');
        $last_name = session('lastname');
        $email = session('email');
        $phone = session('phone');
        $clubs = session('clubs');
        $ref_id = session('ref_id');

        session([
            'source' => config('app.url') . '/' . $clubs,
        ]);
        $event_id = $request->input('event_id1');
        $clubs = $request->input('clubs1');
        $price_data = $request->input('price_data1');
        $email2 = $email;
        $lowPrice = $price_data - 1;
        $highPrice = $price_data + 1;
        $order_expires_time = Carbon::now()->addMinutes(config('attendize.checkout_timeout_after'));
        $ticket_id  = '';
        // Event View Data

        $event = Event::findOrFail($event_id);

        $ticketData = DB::select('select * from tickets where event_id = :event_id AND price>=:lowPrice AND price<=:highPrice', ['event_id' => $event_id, 'lowPrice' => $lowPrice, 'highPrice' => $highPrice]);
        if (empty($ticketData)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No tickets Available.',
            ]);
        }
        foreach ($ticketData as $ticketall) {
            $ticket_id = $ticketall->id;
        }
        $ticket = Ticket::find($ticket_id);
        $i = 0;
        $order_total = 0;
        $booking_fee = 0;
        $organiser_booking_fee = 0;
        $total_ticket_quantity = 1;
        $current_ticket_quantity = 1;

        $order_total = $order_total + ($current_ticket_quantity * $ticket->price);
        $booking_fee = $booking_fee + ($current_ticket_quantity * $ticket->booking_fee);
        $organiser_booking_fee = $organiser_booking_fee + ($current_ticket_quantity * $ticket->organiser_booking_fee);


        $validation_rules = [];
        $validation_messages = [];
        $tickets = [];

        $validation_rules['ticket_holder_first_name.' . $i . '.' . $ticket_id] = ['required'];
        $validation_rules['ticket_holder_last_name.' . $i . '.' . $ticket_id] = ['required'];
        $validation_rules['ticket_holder_email.' . $i . '.' . $ticket_id] = ['required', 'email'];
        $validation_rules['ticket_holder_questions.' . $ticket_id . '.' . $i . '.' . ''] = ['required'];

        // Validate Timezone
        $validation_messages['ticket_holder_first_name.' . $i . '.' . $ticket_id . '.required'] = 'Ticket holder ' . ($i + 1) . '\'s first name is required';
        $validation_messages['ticket_holder_last_name.' . $i . '.' . $ticket_id . '.required'] = 'Ticket holder ' . ($i + 1) . '\'s last name is required';
        $validation_messages['ticket_holder_email.' . $i . '.' . $ticket_id . '.required'] = 'Ticket holder ' . ($i + 1) . '\'s email is required';
        $validation_messages['ticket_holder_email.' . $i . '.' . $ticket_id . '.email'] = 'Ticket holder ' . ($i + 1) . '\'s email appears to be invalid';
        $validation_messages['ticket_holder_questions.' . $ticket_id . '.' . $i . '.' . '' . '.required'] = "This question is required";

        // reservedTicket
        $reservedTickets = new ReservedTickets();
        $reservedTickets->ticket_id = $ticket_id;
        $reservedTickets->event_id = $event_id;
        $reservedTickets->quantity_reserved = $current_ticket_quantity;
        $reservedTickets->expires = $order_expires_time;
        $reservedTickets->session_id = session()->getId();
        $reservedTickets->save();

        $firstNameArray = array($ticket_id => $first_name);
        $lastNameArray = array($ticket_id => $last_name);
        $emailNameArray = array($ticket_id => $email);

        $tickets[] = [
            'ticket'                => $ticket,
            'qty'                   => $current_ticket_quantity,
            'price'                 => ($current_ticket_quantity * $ticket->price),
            'booking_fee'           => ($current_ticket_quantity * $ticket->booking_fee),
            'organiser_booking_fee' => ($current_ticket_quantity * $ticket->organiser_booking_fee),
            'full_price'            => $ticket->price + $ticket->total_booking_fee,
        ];
        $activeAccountPaymentGateway = $event->account->getGateway($event->account->payment_gateway_id);
        $paymentGateway = $activeAccountPaymentGateway ? $activeAccountPaymentGateway->payment_gateway : false;

        session()->put('ticket_order_' . $event->id, [
            'validation_rules'            => $validation_rules,
            'source'            => 'events',
            'validation_messages'         => $validation_messages,
            'event_id'                    => $event->id,
            'order_first_name'          => $first_name,
            'order_last_name'           => $last_name,
            'contact_no'                => $phone,
            'ref_id'                => $ref_id,
            'business_name'             => null,
            'business_tax_number'       => null,
            'business_address_line1'    => null,
            'business_address_line2'    => null,
            'business_address_state'    => null,
            'business_address_city'     => null,
            'business_address_code'     => null,
            'is_embedded'                => null,
            'order_email'               => $email,
            'tickets'                     => $tickets,
            'total_ticket_quantity'       => $total_ticket_quantity,
            'order_started'               => time(),
            'expires'                     => $order_expires_time,
            'reserved_tickets_id'        => $reservedTickets->id,
            'order_total'                => $order_total,
            'booking_fee'                => $booking_fee,
            'organiser_booking_fee'       => $organiser_booking_fee,
            'total_booking_fee'           => $booking_fee + $organiser_booking_fee,
            'order_requires_payment'      => PaymentUtils::requiresPayment($order_total),
            'account_id'                  => $event->account->id,
            'affiliate_referral'          => Cookie::get('affiliate_' . $event_id),
            'account_payment_gateway'     => $activeAccountPaymentGateway,
            'payment_gateway'             => $paymentGateway,
            'ticket_holder_first_name'  => $firstNameArray,
            'ticket_holder_last_name'   => $lastNameArray,
            'ticket_holder_email'        => $emailNameArray,
            'final_email'     => $email2,
            'referal_email' => session('referal_email_accepted') === true ? session('referal_email') : null,
            'referal_first_name' => session('referal_email_accepted') === true ? session('referal_first_name') : null,
            'referal_last_name' => session('referal_email_accepted') === true ? session('referal_last_name') : null,
        ]);
        // New Data




        return response()->json([
            'status'      => 'success',
            'redirectUrl' => route('showEventPayment', [
                'event_id'    => $event_id,
                'is_embedded' => '0'
            ])
        ]);
    }

    public function postBuyNow2(Request $request)
    {
        $email2 = '';

        $first_name = session('firstname');
        $last_name = session('lastname');
        $email = session('email');
        $phone = session('phone');
        $clubs = session('clubs');
        $ref_id = session('ref_id');

        session([
            'source' => config('app.url') . '/' . $clubs,
        ]);

        $event_id = $request->input('event_id2');
        $clubs = $request->input('clubs2');
        $email2 = $email;
        $price_data = $request->input('price_data2');
        $lowPrice = $price_data - 1;
        $highPrice = $price_data + 1;
        $order_expires_time = Carbon::now()->addMinutes(config('attendize.checkout_timeout_after'));
        $ticket_id  = '';
        // Event View Data

        $event = Event::findOrFail($event_id);

        $ticketData = DB::select('select * from tickets where event_id = :event_id AND price>=:lowPrice AND price<=:highPrice', ['event_id' => $event_id, 'lowPrice' => $lowPrice, 'highPrice' => $highPrice]);
        if (empty($ticketData)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No tickets Available.',
            ]);
        }
        foreach ($ticketData as $ticketall) {
            $ticket_id = $ticketall->id;
        }
        $ticket = Ticket::find($ticket_id);
        $i = 0;
        $order_total = 0;
        $booking_fee = 0;
        $organiser_booking_fee = 0;
        $total_ticket_quantity = 1;
        $current_ticket_quantity = 1;

        $order_total = $order_total + ($current_ticket_quantity * $ticket->price);
        $booking_fee = $booking_fee + ($current_ticket_quantity * $ticket->booking_fee);
        $organiser_booking_fee = $organiser_booking_fee + ($current_ticket_quantity * $ticket->organiser_booking_fee);


        $validation_rules = [];
        $validation_messages = [];
        $tickets = [];

        $validation_rules['ticket_holder_first_name.' . $i . '.' . $ticket_id] = ['required'];
        $validation_rules['ticket_holder_last_name.' . $i . '.' . $ticket_id] = ['required'];
        $validation_rules['ticket_holder_email.' . $i . '.' . $ticket_id] = ['required', 'email'];
        $validation_rules['ticket_holder_questions.' . $ticket_id . '.' . $i . '.' . ''] = ['required'];

        // Validate Timezone
        $validation_messages['ticket_holder_first_name.' . $i . '.' . $ticket_id . '.required'] = 'Ticket holder ' . ($i + 1) . '\'s first name is required';
        $validation_messages['ticket_holder_last_name.' . $i . '.' . $ticket_id . '.required'] = 'Ticket holder ' . ($i + 1) . '\'s last name is required';
        $validation_messages['ticket_holder_email.' . $i . '.' . $ticket_id . '.required'] = 'Ticket holder ' . ($i + 1) . '\'s email is required';
        $validation_messages['ticket_holder_email.' . $i . '.' . $ticket_id . '.email'] = 'Ticket holder ' . ($i + 1) . '\'s email appears to be invalid';
        $validation_messages['ticket_holder_questions.' . $ticket_id . '.' . $i . '.' . '' . '.required'] = "This question is required";

        // reservedTicket
        $reservedTickets = new ReservedTickets();
        $reservedTickets->ticket_id = $ticket_id;
        $reservedTickets->event_id = $event_id;
        $reservedTickets->quantity_reserved = $current_ticket_quantity;
        $reservedTickets->expires = $order_expires_time;
        $reservedTickets->session_id = session()->getId();
        $reservedTickets->save();

        $firstNameArray = array($ticket_id => $first_name);
        $lastNameArray = array($ticket_id => $last_name);
        $emailNameArray = array($ticket_id => $email);

        $tickets[] = [
            'ticket'                => $ticket,
            'qty'                   => $current_ticket_quantity,
            'price'                 => ($current_ticket_quantity * $ticket->price),
            'booking_fee'           => ($current_ticket_quantity * $ticket->booking_fee),
            'organiser_booking_fee' => ($current_ticket_quantity * $ticket->organiser_booking_fee),
            'full_price'            => $ticket->price + $ticket->total_booking_fee,
        ];
        $activeAccountPaymentGateway = $event->account->getGateway($event->account->payment_gateway_id);
        $paymentGateway = $activeAccountPaymentGateway ? $activeAccountPaymentGateway->payment_gateway : false;

        session()->put('ticket_order_' . $event->id, [
            'validation_rules'            => $validation_rules,
            'source'            => 'events',
            'validation_messages'         => $validation_messages,
            'event_id'                    => $event->id,
            'order_first_name'          => $first_name,
            'order_last_name'           => $last_name,
            'contact_no'                => $phone,
            'ref_id'                => $ref_id,
            'business_name'             => null,
            'business_tax_number'       => null,
            'business_address_line1'    => null,
            'business_address_line2'    => null,
            'business_address_state'    => null,
            'business_address_city'     => null,
            'business_address_code'     => null,
            'is_embedded'                => null,
            'order_email'               => $email,
            'tickets'                     => $tickets,
            'total_ticket_quantity'       => $total_ticket_quantity,
            'order_started'               => time(),
            'expires'                     => $order_expires_time,
            'reserved_tickets_id'        => $reservedTickets->id,
            'order_total'                => $order_total,
            'booking_fee'                => $booking_fee,
            'organiser_booking_fee'       => $organiser_booking_fee,
            'total_booking_fee'           => $booking_fee + $organiser_booking_fee,
            'order_requires_payment'      => PaymentUtils::requiresPayment($order_total),
            'account_id'                  => $event->account->id,
            'affiliate_referral'          => Cookie::get('affiliate_' . $event_id),
            'account_payment_gateway'     => $activeAccountPaymentGateway,
            'payment_gateway'             => $paymentGateway,
            'ticket_holder_first_name'  => $firstNameArray,
            'ticket_holder_last_name'   => $lastNameArray,
            'ticket_holder_email'        => $emailNameArray,
            'final_email'     => $email2,
            'referal_email' => session('referal_email_accepted') === true ? session('referal_email') : null,
            'referal_first_name' => session('referal_email_accepted') === true ? session('referal_first_name') : null,
            'referal_last_name' => session('referal_email_accepted') === true ? session('referal_last_name') : null,
        ]);
        // New Data




        return response()->json([
            'status'      => 'success',
            'redirectUrl' => route('showEventPayment', [
                'event_id'    => $event_id,
                'is_embedded' => '0'
            ])
        ]);
    }

    public function speakerBuyNow(Request $request)
    {
        $key = $request->ref;
        $first_name = '';
        $last_name = '';
        $email = '';
        $user_id = '';
        $phone = '';
        $clubs = '';
        $event_id = '';
        $price_data = 0;
        $email2 = '';
        $spekaerDBData = DB::table('Wo_Clickfunnel_Event_SpeakerData')->where('id', $key)->get();

        foreach ($spekaerDBData as $speakdata) {
            $first_name = $speakdata->first_name;
            $last_name = $speakdata->last_name;
            $email = $speakdata->email_id;
            $user_id = $speakdata->user_id;
            $phone = $speakdata->mobile_no;
            $clubs = $speakdata->club;
            $event_id = $speakdata->event_id;
            $price_data = $speakdata->amount;
        }
        $clubs = strtolower($clubs);
        $email2 = $email;
        session([
            'source' => config('app.url') . '/' . $clubs,
        ]);
        $price_data = (int)$price_data;
        $lowPrice = $price_data - 1;
        $highPrice = $price_data + 1;
        $order_expires_time = Carbon::now()->addMinutes(config('attendize.checkout_timeout_after'));
        $ticket_id  = '';
        // Event View Data

        $event = Event::findOrFail($event_id);

        $ticketData = DB::select('select * from tickets where event_id = :event_id AND price>=:lowPrice AND price<=:highPrice', ['event_id' => $event_id, 'lowPrice' => $lowPrice, 'highPrice' => $highPrice]);
        if (empty($ticketData)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No tickets Available.',
            ]);
        }
        foreach ($ticketData as $ticketall) {
            $ticket_id = $ticketall->id;
        }
        $ticket = Ticket::find($ticket_id);
        $i = 0;
        $order_total = 0;
        $booking_fee = 0;
        $organiser_booking_fee = 0;
        $total_ticket_quantity = 1;
        $current_ticket_quantity = 1;

        $order_total = $order_total + ($current_ticket_quantity * $ticket->price);
        $booking_fee = $booking_fee + ($current_ticket_quantity * $ticket->booking_fee);
        $organiser_booking_fee = $organiser_booking_fee + ($current_ticket_quantity * $ticket->organiser_booking_fee);


        $validation_rules = [];
        $validation_messages = [];
        $tickets = [];

        $validation_rules['ticket_holder_first_name.' . $i . '.' . $ticket_id] = ['required'];
        $validation_rules['ticket_holder_last_name.' . $i . '.' . $ticket_id] = ['required'];
        $validation_rules['ticket_holder_email.' . $i . '.' . $ticket_id] = ['required', 'email'];
        $validation_rules['ticket_holder_questions.' . $ticket_id . '.' . $i . '.' . ''] = ['required'];

        // Validate Timezone
        $validation_messages['ticket_holder_first_name.' . $i . '.' . $ticket_id . '.required'] = 'Ticket holder ' . ($i + 1) . '\'s first name is required';
        $validation_messages['ticket_holder_last_name.' . $i . '.' . $ticket_id . '.required'] = 'Ticket holder ' . ($i + 1) . '\'s last name is required';
        $validation_messages['ticket_holder_email.' . $i . '.' . $ticket_id . '.required'] = 'Ticket holder ' . ($i + 1) . '\'s email is required';
        $validation_messages['ticket_holder_email.' . $i . '.' . $ticket_id . '.email'] = 'Ticket holder ' . ($i + 1) . '\'s email appears to be invalid';
        $validation_messages['ticket_holder_questions.' . $ticket_id . '.' . $i . '.' . '' . '.required'] = "This question is required";

        // reservedTicket
        $reservedTickets = new ReservedTickets();
        $reservedTickets->ticket_id = $ticket_id;
        $reservedTickets->event_id = $event_id;
        $reservedTickets->quantity_reserved = $current_ticket_quantity;
        $reservedTickets->expires = $order_expires_time;
        $reservedTickets->session_id = session()->getId();
        $reservedTickets->save();

        $firstNameArray = array($ticket_id => $first_name);
        $lastNameArray = array($ticket_id => $last_name);
        $emailNameArray = array($ticket_id => $email);

        $tickets[] = [
            'ticket'                => $ticket,
            'qty'                   => $current_ticket_quantity,
            'price'                 => ($current_ticket_quantity * $ticket->price),
            'booking_fee'           => ($current_ticket_quantity * $ticket->booking_fee),
            'organiser_booking_fee' => ($current_ticket_quantity * $ticket->organiser_booking_fee),
            'full_price'            => $ticket->price + $ticket->total_booking_fee,
        ];
        $activeAccountPaymentGateway = $event->account->getGateway($event->account->payment_gateway_id);
        $paymentGateway = $activeAccountPaymentGateway ? $activeAccountPaymentGateway->payment_gateway : false;

        session()->put('ticket_order_' . $event->id, [
            'validation_rules'            => $validation_rules,
            'source'            => 'events',
            'ref_id'                => $user_id,
            'validation_messages'         => $validation_messages,
            'event_id'                    => $event->id,
            'order_first_name'          => $first_name,
            'order_last_name'           => $last_name,
            'contact_no'                => $phone,
            'business_name'             => null,
            'business_tax_number'       => null,
            'business_address_line1'    => null,
            'business_address_line2'    => null,
            'business_address_state'    => null,
            'business_address_city'     => null,
            'business_address_code'     => null,
            'is_embedded'                => null,
            'order_email'               => $email,
            'tickets'                     => $tickets,
            'total_ticket_quantity'       => $total_ticket_quantity,
            'order_started'               => time(),
            'expires'                     => $order_expires_time,
            'reserved_tickets_id'        => $reservedTickets->id,
            'order_total'                => $order_total,
            'booking_fee'                => $booking_fee,
            'organiser_booking_fee'       => $organiser_booking_fee,
            'total_booking_fee'           => $booking_fee + $organiser_booking_fee,
            'order_requires_payment'      => PaymentUtils::requiresPayment($order_total),
            'account_id'                  => $event->account->id,
            'affiliate_referral'          => Cookie::get('affiliate_' . $event_id),
            'account_payment_gateway'     => $activeAccountPaymentGateway,
            'payment_gateway'             => $paymentGateway,
            'ticket_holder_first_name'  => $firstNameArray,
            'ticket_holder_last_name'   => $lastNameArray,
            'ticket_holder_email'        => $emailNameArray,
            'final_email'     => $email2
        ]);
        // New Data
        return redirect()->route('showEventPayment', [
            'event_id'    => $event_id,
            'is_embedded' => '0'
        ]);
    }

    // This is the default video for each club when you visit the club page like /london, /london-mayfair /losangeles
    // public function setVideo($club)
    // {
    //     $clubs = [
    //         'globalmanclub' => 'https://www.youtube.com/embed/z8UxYd43uYs?si=IMbBxx9y1mGWDffx',
    //         // 'london' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/academy/The+power+of+your+network.mp4',
    //         'london' => 'https://www.youtube.com/embed/eJU0JfHSCBs?si=Vtm-nyB8Url8YOmE',
    //         'london-mayfair' => 'https://www.youtube.com/embed/PUSyN5Ezbdg?si=u7p8wUSHKOGMyVfS',
    //         'istanbul' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/academy/mirela_london_event.mp4',
    //         'doha' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/doha.mp4',
    //         'windsor' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/academy/mirela_london_event.mp4',

    //         'skopje' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/academy/mirela_london_event.mp4',
    //         'dubai' => 'https://davxegdvkjbks.cloudfront.net/Global+Woman+Club+Dubai+Opening+October+2022.mp4',

    //         'orangecounty' => 'https://davxegdvkjbks.cloudfront.net/Meet_the_regional_director_of_Orange_County_Carla_Cohen.mp4',
    //         'florence' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/VIDEOs/What+makes+a+community+grow+together.mp4',
    //         'amsterdam' => 'https://www.youtube.com/embed/1GsNnyGgdL8?si=5xfrdagqyFa7HElD',
    //         'oslo' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/academy/mirela_london_event.mp4',

    //         'mongolia' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Nara_Mongolia_director.mp4',
    //         'bucharest' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/academy/mirela_london_event.mp4',
    //         'paris' => 'https://www.youtube.com/embed/ol7csFJm0UE?si=2rhuWdaFF60gMiYI',
    //         'zurich' => 'https://globalwomanvideo.s3.amazonaws.com/zurich.mp4',
    //         'stockholm' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/academy/mirela_london_event.mp4',
    //         'losangeles' => 'https://globalwomanvideo.s3.amazonaws.com/los_angeles.mp4',

    //         'kosovo' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/academy/mirela_london_event.mp4',
    //         'nottinghill' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/academy/mirela_london_event.mp4',
    //         'manila' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/demo/lounge/Intro+-+Rayla+Allertsen+Manila.mp4',
    //         'brussels' => 'https://www.youtube.com/embed/eJU0JfHSCBs?si=_0_Cu6GFD4AqRh1-',

    //         'cyprus' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Cyprus/Join+The+Global+Woman+Club+in+Cyprus.mp4',
    //         'chicago' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/academy/mirela_london_event.mp4',
    //         'nottingham' => 'https://media.globalwomanclub.com/events/nottingham.mp4',

    //         'frankfurt' => 'https://media.globalwomanclub.com/events/frankfurt.mp4',
    //         'vienna' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/academy/mirela_london_event.mp4',
    //         'singapore' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/academy/mirela_london_event.mp4',
    //         'tirana' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/promo_tirana_conference.mp4',

    //         'india' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/academy/mirela_london_event.mp4',
    //         'johannesburg' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/academy/mirela_london_event.mp4',

    //         'accra' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Events+Videos/Linda+Video+Landscape.mp4',
    //         'online' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Events+Videos/Linda+Video+Landscape.mp4',
    //         'newyork' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/Video-456.mp4',

    //         'birmingham' => 'https://globalwomanlive-logsbucket-b8t5h1d5b5io.s3.eu-west-2.amazonaws.com/academy/mirela_london_event.mp4',

    //         'siliconvalley' => 'https://www.youtube.com/embed/ZbkZ-J0Srqg?si=dFgyOX6HBv9DZD1l',
    //         'berlin' => 'https://www.youtube.com/embed/ZbkZ-J0Srqg?si=dFgyOX6HBv9DZD1l',
    //         'vancouver' => 'https://www.youtube.com/embed/ZbkZ-J0Srqg?si=dFgyOX6HBv9DZD1l',
    //         'toronto' => 'https://www.youtube.com/embed/ZbkZ-J0Srqg?si=dFgyOX6HBv9DZD1l',
    //         'manchester' => 'https://www.youtube.com/embed/rloKsPOFQz4?si=t2DSYS1pBIPeYrK0',
    //         'liverpool' => 'https://www.youtube.com/embed/eJU0JfHSCBs?si=S9-qxrCNywPtmeWo',
    //         'cheshire' => 'https://www.youtube.com/embed/rloKsPOFQz4?si=t2DSYS1pBIPeYrK0',
    //         'lisbon' => 'https://www.youtube.com/embed/rloKsPOFQz4?si=t2DSYS1pBIPeYrK0',
    //         'cobham' => 'https://www.youtube.com/embed/rloKsPOFQz4?si=t2DSYS1pBIPeYrK0',
    //         'malta' => 'https://www.youtube.com/embed/rloKsPOFQz4?si=t2DSYS1pBIPeYrK0',
    //         'leeds' => 'https://www.youtube.com/embed/rloKsPOFQz4?si=t2DSYS1pBIPeYrK0',
    //         'norwich' => 'https://www.youtube.com/embed/rloKsPOFQz4?si=t2DSYS1pBIPeYrK0',
    //         'essex' => 'https://www.youtube.com/embed/rloKsPOFQz4?si=t2DSYS1pBIPeYrK0',
    //         'birmingham' => 'https://www.youtube.com/embed/rloKsPOFQz4?si=t2DSYS1pBIPeYrK0',
    //         'notingham' => 'https://www.youtube.com/embed/rloKsPOFQz4?si=t2DSYS1pBIPeYrK0',
    //         'geneva' => 'https://www.youtube.com/embed/ZbkZ-J0Srqg?si=dFgyOX6HBv9DZD1l',
    //         'investorsclub' => 'https://www.youtube.com/embed/ZbkZ-J0Srqg?si=dFgyOX6HBv9DZD1l',
    //         'munich' => 'https://www.youtube.com/embed/52Pgg_eI91w?si=I7ePG74Z5QYa6Q59',
    //         'abudhabi' => 'https://www.youtube.com/embed/ZbkZ-J0Srqg?si=dFgyOX6HBv9DZD1l',
    //         'albaniandiaspora' => 'https://www.youtube.com/embed/ZbkZ-J0Srqg?si=dFgyOX6HBv9DZD1l',
    //         'metrodc' => 'https://www.youtube.com/embed/OoeLoWHhZMk?si=2nRLMDI4ugXPduNg',
    //         'houston' => 'https://www.youtube.com/embed/eJU0JfHSCBs?si=Vtm-nyB8Url8YOmE',
    //         'dallas' => 'https://www.youtube.com/embed/eJU0JfHSCBs?si=Vtm-nyB8Url8YOmE',
    //     ];

    //     return $clubs[$club];
    // }

    // public function getTemplate($club)
    // {
    //     $clubs = [
    //         'london' => 'Default',
    //         'london-mayfair' => 'LondonMayFair',
    //         'birmingham' => 'Default',
    //         'istanbul' => 'Default',
    //         'doha' => 'Doha',
    //         'windsor' => 'Default',

    //         'skopje' => 'Default',
    //         'dubai' => 'Dubai',

    //         'orangecounty' => 'Orangecounty',
    //         'florence' => 'Florence',
    //         'amsterdam' => 'Amsterdam',
    //         'oslo' => 'Default',

    //         'mongolia' => 'Default',
    //         'bucharest' => 'Default',
    //         'paris' => 'Paris',
    //         'stockholm' => 'Stockholm',
    //         'losangeles' => 'Losangeles',

    //         'kosovo' => 'Kosovo',
    //         'nottinghill' => 'Default',
    //         'manila' => 'Default',
    //         'brussels' => 'Default',

    //         'cyprus' => 'Cyprus',
    //         'chicago' => 'Default',
    //         'nottingham' => 'Nottingham',

    //         'frankfurt' => 'Default',
    //         'singapore' => 'Default',
    //         'tirana' => 'Tirana',

    //         'india' => 'Default',
    //         'johannesburg' => 'Default',

    //         'accra' => 'Accra',
    //         'online' => 'Accra',
    //         'newyork' => 'Newyork',

    //         'globalmanclub' => 'Default',
    //         'vienna' => 'Default',
    //         'siliconvalley' => 'Default',
    //         'berlin' => 'Default',
    //         'manchester' => 'Default',
    //         'liverpool' => 'Default',
    //         'cheshire' => 'Default',
    //         'lisbon' => 'Default',
    //         'cobham' => 'Default',
    //         'malta' => 'Default',
    //         'leeds' => 'Default',
    //         'norwich' => 'Default',
    //         'essex' => 'Default',
    //         'birmingham' => 'Default',
    //         'notingham' => 'Default',
    //         'geneva' => 'Default',
    //         'investorsclub' => 'Default',
    //         'munich' => 'Default',
    //         'albaniandiaspora' => 'Default',
    //         'metrodc' => 'Default',
    //         'houston' => 'Default',
    //         'dallas' => 'Default',
    //         'abudhabi' => 'Default',
    //         'zurich' => 'Default',
    //         'vancouver' => 'Default',
    //         'toronto' => 'Default',
    //     ];

    //     // if (is_default_template($club)) {
    //     //     return 'Default';
    //     // }

    //     return $clubs[$club];
    // }

    // public function getCurrentTime($club)
    // {
    //     $clubs = [
    //         'globalmanclub' => 'Europe/London',
    //         'london' => 'Europe/London',
    //         'london-mayfair' => 'Europe/London',
    //         'birmingham' => 'Europe/London',
    //         'istanbul' => 'Europe/Istanbul',
    //         'doha' => 'Asia/Qatar',
    //         'windsor' => 'America/Toronto',

    //         'skopje' => 'Europe/Skopje',
    //         'dubai' => 'Asia/Dubai',

    //         'orangecounty' => 'America/Los_Angeles',
    //         'florence' => 'Europe/Rome',
    //         'amsterdam' => 'Europe/Amsterdam',
    //         'oslo' => 'Europe/Oslo',

    //         'mongolia' => 'Asia/Ulaanbaatar',
    //         'bucharest' => 'Europe/Bucharest',
    //         'paris' => 'Europe/Paris',
    //         'zurich' => 'Europe/Zurich',
    //         'stockholm' => 'Europe/Stockholm',
    //         'losangeles' => 'America/Los_Angeles',

    //         'kosovo' => 'Europe/Belgrade',
    //         'nottinghill' => 'Europe/London',
    //         'manila' => 'Asia/Manila',
    //         'brussels' => 'Europe/Brussels',

    //         'cyprus' => 'Asia/Nicosia',
    //         'chicago' => 'America/Chicago',
    //         'nottingham' => 'Europe/London',

    //         'frankfurt' => 'Europe/Amsterdam',
    //         'vienna' => 'Europe/Vienna',
    //         'singapore' => 'Asia/Singapore',
    //         'tirana' => 'Europe/Tirane',

    //         'india' => 'Asia/Kolkata',
    //         'johannesburg' => 'Africa/Johannesburg',

    //         'accra' => 'Europe/London',
    //         'online' => 'Europe/London',
    //         'newyork' => 'America/New_York',

    //         'berlin' => 'Europe/Berlin',
    //         'vancouver' => 'America/Vancouver',
    //         'toronto' => 'America/Toronto',
    //         'manchester' => 'Europe/London',
    //         'liverpool' => 'Europe/London',
    //         'cheshire' => 'Europe/London',
    //         'lisbon' => 'Europe/Lisbon',
    //         'cobham' => 'Europe/London',
    //         'malta' => 'Europe/Malta',
    //         'leeds' => 'Europe/London',
    //         'norwich' => 'Europe/London',
    //         'essex' => 'Europe/London',
    //         'birmingham' => 'Europe/London',
    //         'notingham' => 'Europe/London',
    //         'geneva' => 'Europe/Zurich',
    //     ];

    //     if (isset($clubs[$club])) {

    //         return Carbon::now($clubs[$club]);
    //     }

    //     return Carbon::now('Europe/London');
    // }

    public function referralRespond(Request $request): \Illuminate\Http\JsonResponse
    {
        $action = $request->input('action');
        $referalEmail = session('referal_email');

        if (!$referalEmail || !in_array($action, ['accept', 'decline'])) {
            return response()->json(['status' => 'error', 'message' => 'Invalid request'], 400);
        }

        // If accepted then we are going to add the email to the orders
        if ($action === 'accept') {
            session(['referal_email_accepted' => true]);
        } else {
            session(['referal_email' => null]);
            session(['referal_email_accepted' => false]);
        }

        return response()->json(['status' => 'success', 'action' => $action]);
    }
}
