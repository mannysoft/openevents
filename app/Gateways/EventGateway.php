<?php

namespace App\Gateways;

use App\Services\GoHighLevel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\LobbyAuth;
use App\Services\LobbyUnavailableException;
use GuzzleHttp\Exception\GuzzleException;
use App\Attendize\PaymentUtils;
use App\Models\Ticket;
use App\Models\Event;
use App\Services\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\ReservedTickets;
use Illuminate\Support\Facades\Cookie;

class EventGateway
{
    public $lobbyUserInfo;

    public $email;
    public $firstName;
    public $lastName;
    public $mobileNo;
    public $eventId;
    public $club;
    public $percentOff;
    public $priceData;
    public $origPriceData;
    public $purchaseEmail;
    public $isMemberEvent;

    public $returnData;

    /**
     * Populated when bookTicket() aborts due to a business rule (e.g. mentor
     * attempting to book an in-person ticket). Controllers can surface this
     * to the client when bookTicket() returns null.
     */
    public ?string $errorMessage = null;

    /**
     * Message shown to Mentors Club members who try to book any ticket
     * that grants in-person attendance.
     */
    public const MENTOR_INPERSON_BLOCK_MESSAGE = 'Your Mentors Club Membership does not include in-person event tickets. Please upgrade to Entrepreneur or Influencer and contact support@globalwomanclub.com for more information.';

    /**
     * Determine whether the ticket the user is attempting to book grants
     * in-person attendance for the given event.
     */
    public function isInPersonBooking(Event $event): bool
    {
        $type = strtolower((string) ($event->event_ttype ?? ''));

        if ($type === 'in-person') {
            return true;
        }

        if ($type === 'hybrid' && (bool) session('is_hybrid')) {
            return true;
        }

        // Bring-a-friend tickets are only issued against the in-person ticket.
        if ((bool) session('is_bring_friend')) {
            return true;
        }

        return false;
    }

    /**
     * The session's access_token for a guest who registered here while the Lobby could not be
     * reached. Like 'emailExists', it only tells the event pages that she has given her details;
     * nothing sends it to the Lobby. She has no Lobby user id.
     */
    public const NO_LOBBY_ACCOUNT = 'noLobbyAccount';

    public function createUser()
    {
        $user_id2 = '';
        $access_token2 = '';
        $currentTime = now();
        $email = $this->email;
        session(['email' => $email]);
        $firstName = $this->firstName;
        $lastName = $this->lastName;
        $fullName = $firstName . $lastName;
        $fullName = str_replace('-', ' ', $fullName);
        $firstName = str_replace('-', ' ', $firstName);
        $lastName = str_replace('-', ' ', $lastName);
        $mobileNo = $this->mobileNo;
        $eventId = $this->eventId;
        $club = $this->club;

        DB::table('Wo_Clickfunnel_Event_Data_New')->insert(
            [
                'email_id' => $email,
                'full_name' => $fullName,
                'mobile_no' => $mobileNo,
                'created' => $currentTime,
                'club' => $club,
                'event_id' => $eventId
            ]
        );

        add_email_schedule($firstName, $lastName, $email, $eventId, $mobileNo, $club);

        try {
            $emailExists = $this->checkEmailExists($email);
        } catch (GuzzleException|LobbyUnavailableException $e) {
            return $this->continueWithoutLobby($email, $firstName, $lastName, $mobileNo, $e);
        }

        // If already exists then we dont need to create the account.
        if ($emailExists === true) {
            session([
                'avatar' => 'https://davxegdvkjbks.cloudfront.net/upload/photos/d-avatar.jpg',
                'email' => $email,
                'firstname' => $firstName,
                'lastname' => $lastName,
                'phone' => $mobileNo,
                'access_token' => 'emailExists',
                'ref_id' => $this->lobbyUserInfo['user_data']['user_id'],
                'member' => '0',
            ]);

            return response()->json(['status' => "success"]);
        }

        $apiStatus2 = 'fail';

        if (isset($json['message'])) {
            $apiStatus2 = $json['message'];
            $access_token2 = $json['access_token'];
            $user_id2 = $json['userId'];
        }

        // has an error
        if (isset($json['errorMessage']) || $apiStatus2 == 'fail') {

            $client = LobbyAuth::httpClient();

            $password = Str::random(10);

            try {
                $accessToken = $this->lobbyAccessToken();

                $httpRequest = $client->request(
                    "POST",
                    "https://lobby.globalwomanclub.com/api/create-account?access_token=" . $accessToken,
                    [
                        "form_params" => [
                            "server_key" => config('services.lobby_app.server_key'),
                            "username" => str_replace(' ', '', $fullName) . Str::random(5),
                            "first_name" => $firstName,
                            "last_name" => $lastName,
                            "email" => $email,
                            "password" => $password,
                            "confirm_password" => $password,
                        ],
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
                            'Accept'     => 'application/json',
                        ]
                    ]
                );
            } catch (GuzzleException|LobbyUnavailableException $e) {
                return $this->continueWithoutLobby($email, $firstName, $lastName, $mobileNo, $e);
            }

            $response = $httpRequest->getBody();

            $json = json_decode($response, true);

            if (isset($json['access_token'])) {
                $access_token2 = $json['access_token'];
                $user_id2 = $json['user_id'];
                $apiStatus2 = 'success';

                $ghl = new GoHighLevel();
                $locationId = $ghl->locationId;
                $ghl->createUpdateContact([
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $email,
                    'phone' => $mobileNo,
                    'locationId' => $locationId,
                    'tags' => ['events', 'tickets']
                ]);
            } else {
                // The Lobby answered but made no account (registration switched off, or a
                // refusal). She carries on without one, as when it does not answer.
                return $this->continueWithoutLobby($email, $firstName, $lastName, $mobileNo, new LobbyUnavailableException('The Lobby made no account.'));
            }
        }

        if ($apiStatus2 == "success") {
            session([
                'avatar' => 'https://davxegdvkjbks.cloudfront.net/upload/photos/d-avatar.jpg',
                'email' => $email,
                'firstname' => $firstName,
                'lastname' => $lastName,
                'phone' => $mobileNo,
                'access_token' => $access_token2,
                'ref_id' => $user_id2,
                'member' => '0',
            ]);

            return response()->json(['status' => "success"]);
        } else {
            // Not the Lobby's answer: it can hold her details.
            Log::warning('Lobby registration failed', ['event_id' => $this->eventId]);
            return response()->json(['status' => "fail"]);
        }
    }

    /**
     * The Lobby could not be reached, answered with an error, or gave no access token. Her
     * details are already stored and her reminder emails scheduled above, so she carries on
     * with no Lobby account instead of meeting a server error. Before this, a Lobby that was
     * down stopped every registration and every order by a guest.
     */
    private function continueWithoutLobby($email, $firstName, $lastName, $mobileNo, \Throwable $e)
    {
        // No email or name here: the log is not a place for buyers' details.
        Log::warning('Lobby unavailable: the guest continues without a Lobby account', [
            'event_id' => $this->eventId,
            'error' => class_basename($e),
        ]);

        session([
            'avatar' => 'https://davxegdvkjbks.cloudfront.net/upload/photos/d-avatar.jpg',
            'email' => $email,
            'firstname' => $firstName,
            'lastname' => $lastName,
            'phone' => $mobileNo,
            'access_token' => self::NO_LOBBY_ACCOUNT,
            'ref_id' => null,
            'member' => '0',
        ]);

        return response()->json(['status' => "success"]);
    }

    /**
     * @throws GuzzleException when the Lobby does not answer, or answers with an error status
     * @throws LobbyUnavailableException when it answers with no access token
     */
    private function lobbyAccessToken(): string
    {
        $accessToken = (new LobbyAuth())->getAccessToken();

        if (! is_string($accessToken) || $accessToken === '') {
            throw new LobbyUnavailableException('The Lobby gave no access token.');
        }

        return $accessToken;
    }

    public function checkEmailExists($email)
    {
        $client = LobbyAuth::httpClient();

        $accessToken = $this->lobbyAccessToken();

        $request = $client->request(
            "POST",
            "https://lobby.globalwomanclub.com/api/get-user-data-email?access_token=" . $accessToken,
            [
                "form_params" => [
                    "server_key" => config('services.lobby_app.server_key'),
                    "fetch" => 'user_data',
                    "email" => $email,
                ],
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
                    'Accept'     => 'application/json',
                ]
            ]
        );

        $response = $request->getBody();

        $json = json_decode($response, true);

        if (isset($json['api_status']) && $json['api_status'] == 200) {
            $this->lobbyUserInfo = $json;
            return true;
        }

        return false;
    }

    public function getOrder($eventId = null)
    {
        $order_session = session()->get('ticket_order_' . $eventId);
        $event = Event::findOrFail($eventId);
        if ($order_session) {
            $order = new Order($order_session['order_total'], $order_session['total_booking_fee'], $event);
            $order->calculateFinalCosts();

            return $order;
        }
    }

    public $ticket;
    public $ticketId;

    public function getTicketDetails($isMemberEvent, $event_id, $lowPrice, $highPrice)
    {
        // If member event then its a free event
        if ($isMemberEvent == null) {
            $ticketData = DB::select('select * from tickets where event_id = :event_id AND price>:lowPrice AND price<:highPrice', ['event_id' => $event_id, 'lowPrice' => $lowPrice, 'highPrice' => $highPrice]);

            // If hybrid then it should be a in-person event
            if (session('is_hybrid')) {
                $ticketData = DB::select('select * from tickets where event_id = :event_id AND price>:lowPrice AND price<:highPrice AND title = :title', ['event_id' => $event_id, 'lowPrice' => $lowPrice, 'highPrice' => $highPrice, 'title' => 'Inperson Networking Ticket']);
                // $ticketData = Ticket::query()
                //     ->where('event_id', $event_id)
                //     ->whereBetween('price', [$lowPrice, $highPrice])
                //     ->where('title', 'Inperson Networking Ticket')
                //     ->get();
                // dd($lowPrice, $highPrice, $ticketData);
                // dd($ticketData);
            }

            // If hybrid then it should be a in-person event
            if (session('is_bring_friend')) {
                $ticketData = DB::select('select * from tickets where event_id = :event_id AND price>:lowPrice AND price<:highPrice', ['event_id' => $event_id, 'lowPrice' => $lowPrice, 'highPrice' => $highPrice]);
            }

            if (empty($ticketData)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No tickets Available.',
                ]);
            }
            foreach ($ticketData as $ticketall) {
                $ticket_id = $ticketall->id;
                $this->ticketId = $ticket_id;
            }
            $ticket = Ticket::find($ticket_id);
            $this->ticket = $ticket;
        } else {
            $ticket = Ticket::where('event_id', $event_id)->first();
            $ticket_id = $ticket->id;

            $this->ticket = $ticket;
            $this->ticketId = $ticket_id;
        }
    }

    public function getTickets($ticket, $current_ticket_quantity = 1)
    {
        $ticketPrice = $ticket->price;
        if (session('is_bring_friend')) {
            
            $ticketPrice = bring_friend_price($ticketPrice);
        }

        $tickets[] = [
            'ticket' => $ticket,
            'qty' => $current_ticket_quantity,
            'price' => $current_ticket_quantity * $ticketPrice,
            'booking_fee' => $current_ticket_quantity * $ticket->booking_fee,
            'organiser_booking_fee' => $current_ticket_quantity * $ticket->organiser_booking_fee,
            'full_price' => $ticketPrice + $ticket->total_booking_fee,
        ];

        return $tickets;
    }

    public function bookTicket($request = null)
    {
        $email2 = '';
        $first_name = $this->firstName;
        $last_name = $this->lastName;
        $email = $this->email;
        $phone = $this->mobileNo;
        $clubs = $this->club;
        $ref_id = session('ref_id');

        session([
            'source' => config('app.url') . '/' . $clubs,
        ]);

        $event_id = $this->eventId;
        $price_data = $this->priceData;
        $purchaseEmail = $this->purchaseEmail;

        if ($email == '') {
            $email = $purchaseEmail;
        }
        $email2 = $email;

        // Business rule: Mentors Club members are not allowed to book in-person
        // event tickets. Enforce here as a defense-in-depth check in addition to
        // the controller-level guard.
        $eventForCheck = Event::find($event_id);
        if ($eventForCheck && $this->isInPersonBooking($eventForCheck) && is_mentor($email)) {
            $this->errorMessage = self::MENTOR_INPERSON_BLOCK_MESSAGE;

            return null;
        }

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
        if (session('is_bring_friend')) {
            $price_data = $this->origPriceData;
            $lowPrice = $price_data - 1;
            $highPrice = $price_data + 1;
        }
        $order_expires_time = Carbon::now()->addMinutes(config('attendize.checkout_timeout_after'));
        $ticket_id  = '';
        // Event View Data

        $event = Event::findOrFail($event_id);

        $this->getTicketDetails($this->isMemberEvent, $event_id, $lowPrice, $highPrice);
        $ticket = $this->ticket;
        $ticket_id = $this->ticketId;

        $i = 0;
        $order_total = 0;
        $booking_fee = 0;
        $organiser_booking_fee = 0;
        $total_ticket_quantity = 1;
        $current_ticket_quantity = 1;
        $ticketPrice = $ticket->price;

        if (session('is_bring_friend')) {
            
            $ticketPrice = bring_friend_price($ticketPrice);
        }

        $order_total = $order_total + ($current_ticket_quantity * $ticketPrice);
        $booking_fee = $booking_fee + ($current_ticket_quantity * $ticket->booking_fee);
        $organiser_booking_fee = $organiser_booking_fee + ($current_ticket_quantity * $ticket->organiser_booking_fee);


        $validation_rules = [];
        $validation_messages = [];
        // $tickets = [];

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

        $tickets = $this->getTickets($ticket, $current_ticket_quantity);

        $activeAccountPaymentGateway = $event->account->getGateway($event->account->payment_gateway_id);
        $paymentGateway = $activeAccountPaymentGateway ? $activeAccountPaymentGateway->payment_gateway : false;

        $discountAmount = 0;
        if ($this->percentOff != null && $this->percentOff != 0) {
            $discountAmount = ($this->percentOff / 100) * $order_total;

            session(['discountAmount' => $discountAmount]);
        }

        if (session('percent_off') != null && session('percent_off') != 0) {
            $discountAmount = (session('percent_off') / 100) * $order_total;

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

        return $event_id;
    }
}
