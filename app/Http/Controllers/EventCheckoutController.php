<?php

namespace App\Http\Controllers;

use App\Attendize\PaymentUtils;
use App\Events\OrderCompletedEvent;
use App\Gateways\EventGateway;
use App\Models\Account;
use App\Models\AccountPaymentGateway;
use App\Models\Affiliate;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\EventStats;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentGateway;
use App\Models\QuestionAnswer;
use App\Models\ReservedTickets;
use App\Models\Ticket;
use App\Services\GoHighLevel;
use App\Services\Order as OrderService;
use Artisan;
use App\Services\PaymentGateway\Factory as PaymentGatewayFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mail;
use PDF;
use PhpSpec\Exception\Exception;
use Validator;
use App\Models\LoungePoint;
use App\Models\Currency;
use App\Models\LobbyPoint;
use App\Models\LobbyUser;
use App\Models\LoungeClub;
use App\Services\EventPaymentLookup;
use App\Services\PointsRedemptionGuard;
use App\Services\StripeGateway;

class EventCheckoutController extends Controller
{
    // Seconds. saveUserLobbyEvent() runs once per attendee inside completeOrder()'s transaction, which
    // holds the ticket rows, and nothing reads its reply. FPM kills a request at 30 s, and inside that
    // transaction the kill would roll back an order the buyer has paid for, so one request stops making
    // the call once it has spent LOBBY_EVENT_BUDGET. The Lambda has not been timed.
    private const LOBBY_EVENT_CONNECT_TIMEOUT = 2;
    private const LOBBY_EVENT_TIMEOUT = 5;
    private const LOBBY_EVENT_BUDGET = 10;

    private float $lobbyEventSeconds = 0;

    /**
     * Is the checkout in an embedded Iframe?
     *
     * @var bool
     */
    protected $is_embedded;

    /**
     * EventCheckoutController constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        /*
         * See if the checkout is being called from an embedded iframe.
         */
        $this->is_embedded = $request->get('is_embedded') == '1';
    }

    /**
     * Validate a ticket request. If successful reserve the tickets and redirect to checkout
     *
     * @param Request $request
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function postValidateTickets(Request $request, $event_id)
    {
        /*
         * Order expires after X min
         */
        $order_expires_time = Carbon::now()->addMinutes(config('attendize.checkout_timeout_after'));

        $event = Event::findOrFail($event_id);

        if (!$request->has('tickets')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No tickets selected',
            ]);
        }

        $ticket_ids = $request->get('tickets');

        /*
         * Remove any tickets the user has reserved
         */
        ReservedTickets::where('session_id', '=', session()->getId())->delete();

        /*
         * Go though the selected tickets and check if they're available
         * , tot up the price and reserve them to prevent over selling.
         */

        $validation_rules = [];
        $validation_messages = [];
        $tickets = [];
        $order_total = 0;
        $total_ticket_quantity = 0;
        $booking_fee = 0;
        $organiser_booking_fee = 0;
        $quantity_available_validation_rules = [];

        foreach ($ticket_ids as $ticket_id) {
            $current_ticket_quantity = (int)$request->get('ticket_' . $ticket_id);

            if ($current_ticket_quantity < 1) {
                continue;
            }

            $total_ticket_quantity = $total_ticket_quantity + $current_ticket_quantity;
            $ticket = Ticket::find($ticket_id);
            $ticket_quantity_remaining = $ticket->quantity_remaining;
            $max_per_person = min($ticket_quantity_remaining, $ticket->max_per_person);

            $quantity_available_validation_rules['ticket_' . $ticket_id] = [
                'numeric',
                'min:' . $ticket->min_per_person,
                'max:' . $max_per_person
            ];

            $quantity_available_validation_messages = [
                'ticket_' . $ticket_id . '.max' => 'The maximum number of tickets you can register is ' . $ticket_quantity_remaining,
                'ticket_' . $ticket_id . '.min' => 'You must select at least ' . $ticket->min_per_person . ' tickets.',
            ];

            $validator = Validator::make(
                ['ticket_' . $ticket_id => (int)$request->get('ticket_' . $ticket_id)],
                $quantity_available_validation_rules,
                $quantity_available_validation_messages
            );

            if ($validator->fails()) {
                return response()->json([
                    'status'   => 'error',
                    'messages' => $validator->messages()->toArray(),
                ]);
            }

            $order_total = $order_total + ($current_ticket_quantity * $ticket->price);
            $booking_fee = $booking_fee + ($current_ticket_quantity * $ticket->booking_fee);
            $organiser_booking_fee = $organiser_booking_fee + ($current_ticket_quantity * $ticket->organiser_booking_fee);

            $tickets[] = [
                'ticket'                => $ticket,
                'qty'                   => $current_ticket_quantity,
                'price'                 => ($current_ticket_quantity * $ticket->price),
                'booking_fee'           => ($current_ticket_quantity * $ticket->booking_fee),
                'organiser_booking_fee' => ($current_ticket_quantity * $ticket->organiser_booking_fee),
                'full_price'            => $ticket->price + $ticket->total_booking_fee,
            ];

            /*
             * Reserve the tickets for X amount of minutes
             */
            $reservedTickets = new ReservedTickets();
            $reservedTickets->ticket_id = $ticket_id;
            $reservedTickets->event_id = $event_id;
            $reservedTickets->quantity_reserved = $current_ticket_quantity;
            $reservedTickets->expires = $order_expires_time;
            $reservedTickets->session_id = session()->getId();
            $reservedTickets->save();

            for ($i = 0; $i < $current_ticket_quantity; $i++) {
                /*
                 * Create our validation rules here
                 */
                $validation_rules['ticket_holder_first_name.' . $i . '.' . $ticket_id] = ['required'];
                $validation_rules['ticket_holder_last_name.' . $i . '.' . $ticket_id] = ['required'];
                $validation_rules['ticket_holder_email.' . $i . '.' . $ticket_id] = ['required', 'email'];

                $validation_messages['ticket_holder_first_name.' . $i . '.' . $ticket_id . '.required'] = 'Ticket holder ' . ($i + 1) . '\'s first name is required';
                $validation_messages['ticket_holder_last_name.' . $i . '.' . $ticket_id . '.required'] = 'Ticket holder ' . ($i + 1) . '\'s last name is required';
                $validation_messages['ticket_holder_email.' . $i . '.' . $ticket_id . '.required'] = 'Ticket holder ' . ($i + 1) . '\'s email is required';
                $validation_messages['ticket_holder_email.' . $i . '.' . $ticket_id . '.email'] = 'Ticket holder ' . ($i + 1) . '\'s email appears to be invalid';

                /*
                 * Validation rules for custom questions
                 */
                foreach ($ticket->questions as $question) {
                    if ($question->is_required && $question->is_enabled) {
                        $validation_rules['ticket_holder_questions.' . $ticket_id . '.' . $i . '.' . $question->id] = ['required'];
                        $validation_messages['ticket_holder_questions.' . $ticket_id . '.' . $i . '.' . $question->id . '.required'] = "This question is required";
                    }
                }
            }
        }

        if (empty($tickets)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No tickets selected.',
            ]);
        }

        $activeAccountPaymentGateway = $event->account->getGateway($event->account->payment_gateway_id);
        //if no payment gateway configured and no offline pay, don't go to the next step and show user error
        if (empty($activeAccountPaymentGateway) && !$event->enable_offline_payments) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No payment gateway configured',
            ]);
        }

        $paymentGateway = $activeAccountPaymentGateway ? $activeAccountPaymentGateway->payment_gateway : false;

        /*
         * The 'ticket_order_{event_id}' session stores everything we need to complete the transaction.
         */
        session()->put('ticket_order_' . $event->id, [
            'validation_rules'        => $validation_rules,
            'validation_messages'     => $validation_messages,
            'event_id'                => $event->id,
            'tickets'                 => $tickets,
            'total_ticket_quantity'   => $total_ticket_quantity,
            'order_started'           => time(),
            'expires'                 => $order_expires_time,
            'reserved_tickets_id'     => $reservedTickets->id,
            'order_total'             => $order_total,
            'booking_fee'             => $booking_fee,
            'organiser_booking_fee'   => $organiser_booking_fee,
            'total_booking_fee'       => $booking_fee + $organiser_booking_fee,
            'order_requires_payment'  => PaymentUtils::requiresPayment($order_total),
            'account_id'              => $event->account->id,
            'affiliate_referral'      => Cookie::get('affiliate_' . $event_id)
        ]);

        /*
         * If we're this far assume everything is OK and redirect them
         * to the the checkout page.
         */
        if ($request->ajax()) {
            return response()->json([
                'status'      => 'success',
                'isEmbedded' => $this->is_embedded,
                'redirectUrl' => route('showEventCheckout', [
                    'event_id'    => $event_id,
                ]) . '#order_form',
            ]);
        }

        /*
         * Maybe display something prettier than this?
         */
        exit('Please enable Javascript in your browser.');
    }

    /**
     * Show the checkout page
     *
     * @param Request $request
     * @param $event_id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function showEventCheckout(Request $request, $event_id)
    {
        $order_session = session()->get('ticket_order_' . $event_id);

        if (!$order_session || $order_session['expires'] < Carbon::now()) {
            return redirect()->route('showEventPage', ['event_id' => $event_id]);
        }

        $secondsToExpire = Carbon::now()->diffInSeconds($order_session['expires']);

        $event = Event::findorFail($order_session['event_id']);

        $orderService = new OrderService($order_session['order_total'], $order_session['total_booking_fee'], $event);
        $orderService->calculateFinalCosts();

        $data = $order_session + [
            'event'           => $event,
            'secondsToExpire' => $secondsToExpire,
            'is_embedded'     => $this->is_embedded,
            'orderService'    => $orderService
        ];

        return view('Public.ViewEvent.EventPageCheckout', $data);
    }

    public function postValidateOrder(Request $request, $event_id)
    {
        //If there's no session kill the request and redirect back to the event homepage.
        if (!session()->get('ticket_order_' . $event_id)) {
            return response()->json([
                'status'      => 'error',
                'message'     => 'Your session has expired.',
                'redirectUrl' => route('showEventPage', [
                    'event_id' => $event_id,
                ])
            ]);
        }

        $request_data = session()->get('ticket_order_' . $event_id . ".request_data");
        $request_data = (!empty($request_data[0])) ? array_merge($request_data[0], $request->all())
            : $request->all();

        $event = Event::findOrFail($event_id);
        $order = new Order();
        $ticket_order = session()->get('ticket_order_' . $event_id);

        $validation_rules = $ticket_order['validation_rules'];
        $validation_messages = $ticket_order['validation_messages'];

        $order->rules = $order->rules + $validation_rules;
        $order->messages = $order->messages + $validation_messages;

        if ($request->has('is_business') && $request->get('is_business')) {
            // Dynamic validation on the new business fields, only gets validated if business selected
            $businessRules = [
                'business_name' => 'required',
                'business_tax_number' => 'required',
                'business_address_line1' => 'required',
                'business_address_city' => 'required',
                'business_address_code' => 'required',
            ];

            $businessMessages = [
                'business_name.required' => 'Please enter a valid business name',
                'business_tax_number.required' => 'Please enter a valid business tax number',
                'business_address_line1.required' => 'Please enter a valid street address',
                'business_address_city.required' => 'Please enter a valid city',
                'business_address_code.required' => 'Please enter a valid code',
            ];

            $order->rules = $order->rules + $businessRules;
            $order->messages = $order->messages + $businessMessages;
        }

        if (!$order->validate($request->all())) {
            return response()->json([
                'status'   => 'error',
                'messages' => $order->errors(),
            ]);
        }

        return response()->json([
            'status'      => 'success',
            'redirectUrl' => route('showEventPayment', [
                'event_id'    => $event_id,
                'is_embedded' => $this->is_embedded
            ])
        ]);
    }

    public function showEventPayment(Request $request, $event_id)
    {
        // When we refresh then we need to get again the details so that we can display to correct
        // amount for a member
        $event = Event::findOrFail($event_id);
        $eventGateway = new EventGateway();
        $eventGateway->eventId = $event_id;
        $eventGateway->club = $event->club;

        // TODO
        $eventGateway->percentOff = session('percent_off');

        // We can use it in the future when assigning the event to a VIP event
        $event->isVip = session('is_vip');
        $event->is_bring_friend = false;
        // Lets try non VIP first
        $eventGateway->priceData = $event->event_member_non_member_price;
        $eventGateway->origPriceData = $event->event_member_non_member_price;

        $event->is_bring_friend = session('is_bring_friend');
        if ($event->is_bring_friend) {
            $eventGateway->priceData = $event->event_member_non_member_price;
        }

        $eventGateway->purchaseEmail = session('email');
        $eventGateway->isMemberEvent = $request->input('is_member_event'); // Need to get this

        $eventGateway->bookTicket($request);

        $order_session = session()->get('ticket_order_' . $event_id);
        $event = Event::findOrFail($event_id);
        if ($order_session) {
            $payment_gateway = $order_session['payment_gateway'];
            $order_total = $order_session['order_total'];
            $account_payment_gateway = $order_session['account_payment_gateway'];

            $orderService = new OrderService($order_session['order_total'], $order_session['total_booking_fee'], $event);
            $orderService->calculateFinalCosts();
            // totalBookingFee - Tax / 20% for UK
            // dd($orderService->grandTotal);
            // dd($orderService);

            $payment_failed = $request->get('is_payment_failed') ? 1 : 0;

            $secondsToExpire = Carbon::now()->diffInSeconds($order_session['expires']);

            $pointsEnough = false;

            // Check if the points is enough to pay for the event
            $email = session('email') ?? 'test@gmail.com';

            $totalPoints = 0;

            $gbpAmount = $order_total;

            if ($email != null && $email != '') {
                $totalPoints = get_points($email);

                // If the currency is not GBP, convert it to GBP
                if ($event->currency->code !== 'GBP') {
                    $gbpAmount = Currency::getEquivalentRate($event->currency->code, $order_total, ['GBP']);
                    $gbpAmount = $gbpAmount[0]['converted_rate'];
                }

                // dd($gbpAmount); // 25.85
                // dd($gbpAmount * 100); // 25.85

                // Convert the points to GBP. 100 points = 1 GBP
                $pointsTogbp = $totalPoints / 100;

                if ($pointsTogbp >= $gbpAmount) {
                    $pointsEnough = true;
                }

                $pointsEnough = is_points_enough($event, $order_total);
            }

            // Disable for now. TODO: Enable later
            // $pointsEnough = false;

            // Process this via Stripe
            $clientSecret = null;
            $stripePublishableKey = null;
            $stripeAccountId = null;

            $grandTotal = $orderService->grandTotal;

            // If the amount is zero, we should not create a payment intent and just complete the order
            // We can handle this in the frontend by checking the order total and if it's zero,
            // we can skip the payment step and just call the complete order endpoint directly
            if (!PaymentUtils::requiresPayment($grandTotal)) {
                $viewData = [
                    'event' => $event,
                    'tickets' => $order_session['tickets'],
                    'order_total' => $order_total,
                    'orderService' => $orderService,
                    'order_requires_payment'  => PaymentUtils::requiresPayment($grandTotal),
                    'account_payment_gateway' => $account_payment_gateway,
                    'payment_gateway' => $payment_gateway,
                    'secondsToExpire' => $secondsToExpire,
                    'payment_failed' => $payment_failed,
                    'member' => is_member($email),
                ];

                return view('Public.ViewEvent.EventPagePayment', $viewData);
            }

            // Need to get the stripe account id of the director of the club
            $club = LoungeClub::where('slug', $event->club)->first();
            if ($club) {
                $stripeGateway = new StripeGateway();
                if ($club->is_onboarded) {
                    $isAccountExists = $stripeGateway->retrieveAccount($club->stripe_account_id);

                    // Try to check the connected account if still valid
                    // If some reason the account is not valid, we will not use it
                    // Use the platform account instead
                    if ($isAccountExists != false) {
                        $stripeGateway->stripeAccountId = $club->stripe_account_id;
                        $stripeGateway->currencyCode = $event->currency_code ?? 'GBP';
                        $stripeGateway->eventTitle = $event->title;
                        $stripeGateway->eventDate = $event->event_date;
                        $stripeGateway->clubSlug = $club->slug;
                        if ($orderService->totalBookingFee != 0) {
                            // 20% VAT for UK
                            $stripeGateway->taxAmount = $orderService->totalBookingFee * 100;
                        }

                        $grandTotal = $orderService->grandTotal;

                        // Add the speaking slot price if any
                        $grandTotal += session()->has('speaking_slot_details') ? session('speaking_slot_details')->price : 0;
                        $stripeGateway->customer = $stripeGateway->createOrUpdateCustomer(session('email') ?? session('checkout_email'));
                        $stripeGateway->eventId = $event->id;
                        $stripeToken = $stripeGateway->getClientSecret($grandTotal * 100, $club->stripe_account_id);
                        // $stripeToken = $stripeGateway->getClientSecret($order_total * 100, $club->stripe_account_id);
                        $clientSecret = $stripeToken['clientSecret'] ?? null;
                        $stripePublishableKey = $club->publishable_key ?? null;
                        $stripeAccountId = $club->stripe_account_id ?? null;
                    } else {
                        // Remove existing club session if any
                        session()->forget('stripeClientSecret.'.$event->club);
                        session()->forget('stripePaymentIntentId.'.$event->club);

                        // Make sure that the club is marked as not onboarded
                        $club->is_onboarded = false;
                        $club->save();
                    }
                } else {
                    // Lets start using the new payment gateway on the platform account
                    $stripeGateway->stripeAccountId = null;
                    $stripeGateway->currencyCode = $event->currency_code ?? 'GBP';
                    $stripeGateway->eventTitle = $event->title;
                    $stripeGateway->eventDate = $event->event_date;
                    $stripeGateway->clubSlug = $club->slug;
                    if ($orderService->totalBookingFee != 0) {
                        // 20% VAT for UK
                        $stripeGateway->taxAmount = $orderService->totalBookingFee * 100;
                    }

                    $grandTotal = $orderService->grandTotal;

                    // If the amount is zero, we should not create a payment intent and just complete the order
                    // We can handle this in the frontend by checking the order total and if it's zero,
                    // we can skip the payment step and just call the complete order endpoint directly
                    if (!PaymentUtils::requiresPayment($grandTotal)) {
                        $viewData = [
                            'event' => $event,
                            'tickets' => $order_session['tickets'],
                            'order_total' => $order_total,
                            'orderService' => $orderService,
                            'order_requires_payment'  => PaymentUtils::requiresPayment($grandTotal),
                            'account_payment_gateway' => $account_payment_gateway,
                            'payment_gateway' => $payment_gateway,
                            'secondsToExpire' => $secondsToExpire,
                            'payment_failed' => $payment_failed,
                            'member' => is_member($email),
                        ];

                        return view('Public.ViewEvent.EventPagePayment', $viewData);
                    }

                    // Add the speaking slot price if any
                    $grandTotal += session()->has('speaking_slot_details') ? session('speaking_slot_details')->price : 0;

                    $stripeGateway->customer = $stripeGateway->createOrUpdateCustomer(session('email') ?? session('checkout_email'));
                    $stripeGateway->eventId = $event->id;
                    $stripeToken = $stripeGateway->getClientSecret($grandTotal * 100);
                    if ($stripeToken === false) {
                        session()->forget('stripePaymentIntentId.'.$event->club);
                        session()->forget('stripePaymentIntentId');
                        // Refresh the page
                        return redirect()->route('showEventPayment', $event->id);
                    }
                    $clientSecret = $stripeToken['clientSecret'] ?? null;
                }
            }

            $viewData = [
                'event' => $event,
                'tickets' => $order_session['tickets'],
                'order_total' => $order_total,
                'orderService' => $orderService,
                'order_requires_payment'  => PaymentUtils::requiresPayment($order_total),
                'account_payment_gateway' => $account_payment_gateway,
                'payment_gateway' => $payment_gateway,
                'secondsToExpire' => $secondsToExpire,
                'payment_failed' => $payment_failed,
                'pointsEnough' => $pointsEnough,
                'totalPoints' => $totalPoints,
                'gbpAmount' => $gbpAmount,
                'member' => is_member($email),
                // How many points equivalent to the order total
                'equivaletPoints' => $gbpAmount * 100,
                'clientSecret' => $clientSecret,
                'stripePublishableKey' => $stripePublishableKey,
                'stripeAccountId' => $stripeAccountId,
            ];

            return view('Public.ViewEvent.EventPagePayment', $viewData);
        } else {
            return redirect()->to(config('app.url') .'/club/' . $event_id);
        }
    }

    /**
     * Create the order and start the payment for the order via Omnipay
     *
     *
     * @param Request $request
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function postCreateOrder(Request $request, $event_id)
    {
        // Paying with points writes to the Lobby's database, with an amount taken from this
        // request. Refuse it before anything else runs unless PointsRedemptionGuard allows it.
        if ($request->use_points == 1 && ! PointsRedemptionGuard::allows($request->email)) {
            Log::warning('Points payment refused', ['event_id' => $event_id]);

            return redirect()
                ->route('showEventPayment', ['event_id' => $event_id, 'is_payment_failed' => 1])
                ->with('message', 'Paying with points is not available at the moment. Please pay by card.');
        }

        $event = Event::findOrFail($event_id);

        // If not logged in, we can save the user data
        if (session('access_token') == null) {

            $eventGateway = new \App\Gateways\EventGateway();
            $eventGateway->email = $request->email;
            $eventGateway->firstName = $request->firstname;
            $eventGateway->lastName = $request->lastname;
            $eventGateway->mobileNo = $request->phone;
            $eventGateway->eventId = $event_id;
            $eventGateway->club = $event->club;
            $eventGateway->createUser();
        }

        if ($request->phone != null) {
            session(['phone' => $request->phone]);

            session()->put('ticket_order_' . $event_id . '.contact_no', $request->phone);
        }

        // To be tested
        // Zero price -
        // Regular price - NonMember

        // Regular price+VAT - NonMember - okay
        // Regular price+VAT-Discount - NonMember -


        // Regular price with discount NonMember


        // Regular price+VAT - Member - okay
        // Regular price+VAT-Discount - Member

        // Coupon P5ivwhaH


        $eventGateway = new EventGateway();
        $eventGateway->email = $request->email;
        $eventGateway->firstName = $request->firstname;
        $eventGateway->lastName = $request->lastname;
        $eventGateway->mobileNo = $request->phone;
        $eventGateway->eventId = $event_id;
        $eventGateway->club = $event->club;

        $eventGateway->percentOff = $request->input('percent_off');

        // $event->isVip = true; // We can use it in the future when assigning the event to a VIP event
        // Lets try non VIP first
        $eventGateway->priceData = event_price(false, $event, is_member($request->email), session('is_hybrid'));
        $eventGateway->purchaseEmail = $request->email;
        $eventGateway->isMemberEvent = $request->input('is_member_event'); // Need to get this

        $eventGateway->bookTicket($request);

        // Lets search stripe if the same transaction is already processed
        // metadata using email and event_id
        $ticket_order = session()->get('ticket_order_' . $event_id);
        if ($ticket_order == null) {
            Log::info('Already purchased: no ticket order in the session', ['event_id' => $event_id]);
            abort(400, 'You have already purchased a ticket for this event.');
        }

        $payment_gateway_config = $ticket_order['account_payment_gateway']->config + [
            'testMode' => config('attendize.enable_test_payments')
        ];

        if (isset($payment_gateway_config['apiKey'])) {

            $stripe = new \Stripe\StripeClient($payment_gateway_config['apiKey']);

            $stripeEmail = session('email');

            // $charge = $stripe->charges->update(
            //     'ch_3RVP6cPFNZtrH0sz0cW9Y1Fm',
            //     ['customer' => 'cus_SQFTqJp19AfCAa']
            // );

            // Search for customer to get stripeCustomerId
            $customers = $stripe->customers->search([
                'query' => "email:'$stripeEmail'",
            ]);

            if ($customers->count() == 0) {
                $customer = $stripe->customers->create([
                    'name' => session('firstname') . ' ' . session('lastname'),
                    'email' => $stripeEmail,
                ]);

                session(['stripeCustomerId' => $customer->id ?? null]);
            } else {
                // If we have a customer, we can use it for the payment
                session(['stripeCustomerId' => $customers->data[0]->id ?? null]);
            }

            $charges = $stripe->charges->search([
                // 'query' => 'metadata[\'email\']:\'techadmin@globalwomanclub.com\' AND metadata[\'event_id\']:\'2380\' AND status:\'succeeded\'',
                'query' => "metadata['email']:'$stripeEmail' AND metadata['event_id']:'$event_id' AND status:'succeeded'",
            ]);

            // If we have a charge, we can redirect to the order complete page
            if ($charges->count() > 0) {
                Log::info('Already purchased: a succeeded charge exists for this buyer and event', ['event_id' => $event_id]);
                abort(400, 'You have already purchased a ticket for this event.');
            }
        }

        // The charge search above only finds a buyer who came back to the site: her email is
        // written to the charge on return, and only on the platform account searched there.
        // A buyer who paid and never came back has her email on the PaymentIntent instead, on
        // the club's connected account or the platform.
        $priorPayment = (new EventPaymentLookup())->succeededPaymentFor(session('email'), $event_id, $event->club);

        if ($priorPayment !== null) {
            Log::info('Already purchased: a succeeded PaymentIntent exists for this buyer and event', [
                'event_id' => $event_id,
                'payment_intent' => $priorPayment,
            ]);
            abort(400, 'You have already purchased a ticket for this event.');
        }

        $request_data = $ticket_order = session()->get('ticket_order_' . $event_id . ".request_data", [0 => []]);
        $request_data = array_merge($request_data[0], $request->except(['cardnumber', 'cvc']));

        $ticket_order = session()->get('ticket_order_' . $event_id);

        $event = Event::findOrFail($event_id);

        $order_requires_payment = $ticket_order['order_requires_payment'];

        if ($request->use_points == 1) {
            // Check if the points is enough to pay for the event
            $pointsEnough = false;
            // Check if the points is enough to pay for the event
            $email = session('email') ?? 'test@gmail.com';
            // Convert the points to GBP. 100 points = 1 GBP
            $totalPoints = get_points($email);


            $gbpAmount = $totalPoints / 100;

            // if ($gbpAmount >= $order_total) {
            //     $pointsEnough = true;
            // }

            // We always get the total points from lobby
            // When we make transaction, we will deduct the points from the lobby
            // We put ledger records on the lounge
            // $loungePoint = new LoungePoint();
            // $loungePoint->email = $email;
            // $loungePoint->user_id = 1;
            // $loungePoint->event_id = $event_id;
            // $loungePoint->difference = $request->equivalet_points;
            // $loungePoint->save();

            session()->put('ticket_order_' . $event_id . '.order_requires_payment', false);
            session()->put('ticket_order_' . $event_id . '.is_paid_points', true);
            session()->put('ticket_order_' . $event_id . '.points_deducted', $request->equivalet_points);

            // Do not response json data. Just redirect to the order complete page
            return $this->completeOrder($event_id, false);
        }

        if ($order_requires_payment && $request->get('pay_offline') && $event->enable_offline_payments) {
            return $this->completeOrder($event_id, true, true);
        }

        if (!$order_requires_payment) {
            return $this->completeOrder($event_id);
        }

        try {

            $order_service = new OrderService($ticket_order['order_total'], $ticket_order['total_booking_fee'], $event);
            $order_service->calculateFinalCosts();

            $payment_gateway_config = $ticket_order['account_payment_gateway']->config + [
                'testMode' => config('attendize.enable_test_payments')
            ];

            $payment_gateway_factory = new PaymentGatewayFactory();
            $gateway = $payment_gateway_factory->create($ticket_order['payment_gateway']->name, $payment_gateway_config);
            //certain payment gateways require an extra parameter here and there so this method takes care of that
            //and sets certain options for the gateway that can be used when the transaction is started
            $gateway->extractRequestParameters($request);

            //generic data that is needed for most orders
            $order_total = $order_service->getGrandTotal();

            $ticket_order['final_email'] = session('email');
            $order_email = $ticket_order['final_email'];


            if ($order_email != "") {
                $currentTime = Carbon::now();

                $response = $gateway->startTransaction($order_total, $order_email, $event);
            } else {
                $currentTime = Carbon::now();

                return response()->json([
                    'status'  => 'error',
                    'message' => "Payment processing failed. Please relogin and try after sometime.",
                ]);
            }

            if ($response->isSuccessful()) {

                session()->push(
                    'ticket_order_' . $event_id . '.transaction_id',
                    $response->getTransactionReference()
                );

                $additionalData = ($gateway->storeAdditionalData()) ? $gateway->getAdditionalData($response) : array();

                session()->push(
                    'ticket_order_' . $event_id . '.transaction_data',
                    $gateway->getTransactionData() + $additionalData
                );

                $gateway->completeTransaction($additionalData);

                return $this->completeOrder($event_id);
            } elseif ($response->isRedirect()) {

                $additionalData = ($gateway->storeAdditionalData()) ? $gateway->getAdditionalData($response) : array();

                session()->push(
                    'ticket_order_' . $event_id . '.transaction_data',
                    $gateway->getTransactionData() + $additionalData
                );

                Log::info("Redirect url: " . $response->getRedirectUrl());

                $return = [
                    'status'       => 'success',
                    'redirectUrl'  => $response->getRedirectUrl(),
                    'message'      => 'Redirecting to ' . $ticket_order['payment_gateway']->provider_name
                ];

                // GET method requests should not have redirectData on the JSON return string
                if ($response->getRedirectMethod() == 'POST') {
                    $return['redirectData'] = $response->getRedirectData();
                }

                return response()->json($return);
            } else {
                // display error to customer
                return response()->json([
                    'status'  => 'error', //. Error here
                    'message' => $response->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error($e);
            Log::error('Sorry, there was an error processing your payment. Please try again.');
            $error = 'Sorry, there was an error processing your payment. Please try again.';
        }

        if ($error) {
            return response()->json([
                'status'  => 'error',
                'message' => $error,
            ]);
        }
    }

    /**
     * Handles the return when a payment is off site
     *
     * @param Request $request
     * @param $event_id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function showEventCheckoutPaymentReturn(Request $request, $event_id)
    {
        $ticket_order = session()->get('ticket_order_' . $event_id);

        if ($ticket_order == null) {
            return response()->redirectToRoute('showEventPage', [
                'event_id' => $event_id,
            ]);
        }

        // https://events.test/e/2572/checkout/success?is_payment_successful=1&payment_intent=pi_3SDaKtPFNZtrH0sz2I7ZSS5s&payment_intent_client_secret=pi_3SDaKtPFNZtrH0sz2I7ZSS5s_secret_oL4BepTqpoBUUqvcilgmAvz3K&redirect_status=succeeded

        // TODO: Process the payment intent here for Stripe SCA
        // If we have session('stripeClientSecret') meaning that its connected to other
        // stripe account via Connect and we need to process the payment here
        $event = Event::findOrFail($event_id);
        if (session('stripeClientSecret.'.$event->club) != null) {
            if (request('is_payment_successful') == 1 && request('is_payment_successful') != null) {

                $stripeGateway = new StripeGateway();

                $event = Event::findOrFail($event_id);

                // Need to get the stripe account id of the director of the club
                $club = LoungeClub::where('slug', $event->club)->first();
                if ($club) {
                    if ($club->is_onboarded) {
                        $stripeGateway->stripeAccountId = $club->stripe_account_id;
                        session()->put('ticket_order_' . $event_id . '.stripe_account_id', $club->stripe_account_id);
                    }
                }

                $stripeGateway->getCharge(session('stripePaymentIntentId.'.$event->club));
                $stripeGateway->updateChargeMeta();

                // We could also include the customer_id in orders table for future reference
                // session()->put('ticket_order_' . $event_id . '.customer_id', $stripe

                // Check if the payment intent is valid
                // Payment successful
                if ($ticket_order['order_first_name'] == null) {
                    session()->put('ticket_order_' . $event_id . '.order_first_name', session('checkout_firstname') ?? session('firstname'));
                    session()->put('firstname', session('checkout_firstname'));
                }

                if ($ticket_order['order_last_name'] == null) {
                    session()->put('ticket_order_' . $event_id . '.order_last_name', session('checkout_lastname') ?? session('lastname'));
                    session()->put('lastname', session('checkout_lastname'));
                }

                if ($ticket_order['order_email'] == null) {
                    session()->put('ticket_order_' . $event_id . '.order_email', session('checkout_email') ?? session('email'));
                    session()->put('email', session('checkout_email'));
                }

                if ($ticket_order['contact_no'] == null) {
                    session()->put('ticket_order_' . $event_id . '.contact_no', session('checkout_phone') ?? session('phone'));
                    session()->put('phone', session('checkout_phone'));
                }

                session()->push('ticket_order_' . $event_id . '.transaction_id', $stripeGateway->latestCharge);
                return $this->completeOrder($event_id, false);
            }
        }

        $payment_gateway_config = $ticket_order['account_payment_gateway']->config + [
            'testMode' => config('attendize.enable_test_payments')
        ];

        $payment_gateway_factory = new PaymentGatewayFactory();
        $gateway = $payment_gateway_factory->create($ticket_order['payment_gateway']->name, $payment_gateway_config);
        $gateway->extractRequestParameters($request);
        $response = $gateway->completeTransaction($ticket_order['transaction_data'][0]);


        if ($response->isSuccessful()) {
            session()->push('ticket_order_' . $event_id . '.transaction_id', $response->getTransactionReference());
            return $this->completeOrder($event_id, false);
        } else {
            session()->flash('message', $response->getMessage());
            return response()->redirectToRoute('showEventPayment', [
                'event_id'          => $event_id,
                'is_payment_failed' => 1,
            ]);
        }
    }

    /**
     * Complete an order
     *
     * @param $event_id
     * @param bool|true $return_json
     * @param bool|false $pay_offline
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function completeOrder($event_id, $return_json = true, $pay_offline = false)
    {
        DB::beginTransaction();

        try {

            $order = new Order();
            // Store the club commission for later use when we create the order
            $order->club_commission = session('club_commission');
            $ticket_order = session()->get('ticket_order_' . $event_id);
            // Not kept in the session: a failed attempt must not mark a later card payment unpaid.
            if ($pay_offline) {
                $ticket_order['pay_offline'] = true;
            }
            $order->is_paid_points = $ticket_order['is_paid_points'] ?? false;
            $order->points_deducted = $ticket_order['points_deducted'] ?? null;

            //Log::info("Ticket order: " . json_encode($ticket_order));

            if (isset($ticket_order['request_data'])) {
                // could be a free ticket
                $request_data = $ticket_order['request_data'][0];
            } else {
                $request_data = $ticket_order;
            }

            $event = Event::findOrFail($ticket_order['event_id']);

            // Paying with points writes to the Lobby's database. An order marked as paid with
            // points is refused unless PointsRedemptionGuard allows it, whatever the session says.
            if ($order->is_paid_points == true && ! PointsRedemptionGuard::allows($ticket_order['order_email'] ?? null)) {
                throw new \RuntimeException('Points payment refused: no verified Lobby sign-in for this order.');
            }

            // If paid via points, no payment required
            if ($order->is_paid_points == true && $order->points_deducted > 0) {

                $totalPoints = get_points($ticket_order['order_email']);

                $lobbyUser = LobbyUser::where('email', $ticket_order['order_email'])->first();

                if ($lobbyUser) {

                    $lobbyPoint = new LobbyPoint();
                    $lobbyPoint->user_id = $lobbyUser->user_id;
                    $lobbyPoint->points = '-'.$order->points_deducted;
                    $lobbyPoint->description = 'Purchase ticket for event: ' . $event->title;
                    $lobbyPoint->total_points = $totalPoints - $order->points_deducted;
                    $lobbyPoint->save();

                    $lobbyUser->points = $lobbyUser->points - $order->points_deducted;
                    $lobbyUser->save();
                }
            }

            $attendee_increment = 1;
            $ticket_questions = isset($request_data['ticket_holder_questions']) ? $request_data['ticket_holder_questions'] : [];

            /*
             * Create the order
             */
            if (isset($ticket_order['transaction_id'])) {
                $order->transaction_id = $ticket_order['transaction_id'][0];
            }

            if (isset($ticket_order['stripe_account_id'])) {
                $order->stripe_account_id = $ticket_order['stripe_account_id'];
            }

            if (isset($ticket_order['transaction_data'][0]['payment_intent'])) {
                $order->payment_intent = $ticket_order['transaction_data'][0]['payment_intent'];
            }

            if ($ticket_order['order_requires_payment'] && !isset($request_data['pay_offline'])) {
                $order->payment_gateway_id = $ticket_order['payment_gateway']->id;
            }
            if (isset($ticket_order['source'])) {
                $order->first_name = sanitise($ticket_order['order_first_name']);
                $order->last_name = sanitise($ticket_order['order_last_name']);
                $order->contact_no = sanitise($ticket_order['contact_no']);
                $order->email = sanitise($ticket_order['order_email']);
                $order->order_status_id = isset($ticket_order['pay_offline']) ? config('attendize.order.awaiting_payment') : config('attendize.order.complete');
                $order->amount = $ticket_order['order_total'];
                $order->booking_fee = $ticket_order['booking_fee'];
                $order->organiser_booking_fee = $ticket_order['organiser_booking_fee'];
                $order->discount = 0.00;
                $order->account_id = $event->account->id;
                $order->event_id = $ticket_order['event_id'];
                $order->is_payment_received = isset($ticket_order['pay_offline']) ? 0 : 1;
            } else {
                $order->first_name = sanitise($request_data['order_first_name']);
                $order->last_name = sanitise($request_data['order_last_name']);
                $order->contact_no = sanitise($request_data['contact_no']);
                $order->email = sanitise($request_data['order_email']);
                $order->order_status_id = isset($request_data['pay_offline']) ? config('attendize.order.awaiting_payment') : config('attendize.order.complete');
                $order->amount = $ticket_order['order_total'];
                $order->booking_fee = $ticket_order['booking_fee'];
                $order->organiser_booking_fee = $ticket_order['organiser_booking_fee'];
                $order->discount = 0.00;
                $order->account_id = $event->account->id;
                $order->event_id = $ticket_order['event_id'];
                $order->is_payment_received = isset($request_data['pay_offline']) ? 0 : 1;

                // Business details is selected, we need to save the business details
                if (isset($request_data['is_business']) && (bool)$request_data['is_business']) {
                    $order->is_business = $request_data['is_business'];
                    $order->business_name = sanitise($request_data['business_name']);
                    $order->business_tax_number = sanitise($request_data['business_tax_number']);
                    $order->business_address_line_one = sanitise($request_data['business_address_line1']);
                    $order->business_address_line_two  = sanitise($request_data['business_address_line2']);
                    $order->business_address_state_province  = sanitise($request_data['business_address_state']);
                    $order->business_address_city = sanitise($request_data['business_address_city']);
                    $order->business_address_code = sanitise($request_data['business_address_code']);
                }
            }


            // Calculating grand total including tax
            $orderService = new OrderService($ticket_order['order_total'], $ticket_order['total_booking_fee'], $event);
            $orderService->calculateFinalCosts();

            $order->taxamt = $orderService->getTaxAmount();
            $order->is_member = is_member(session('email'));
            $order->save();

            /*
             * Update affiliates stats stats
             */
            if ($ticket_order['affiliate_referral']) {
                $affiliate = Affiliate::where('name', '=', $ticket_order['affiliate_referral'])
                    ->where('event_id', '=', $event_id)->first();
                $affiliate->increment('sales_volume', $order->amount + $order->organiser_booking_fee);
                $affiliate->increment('tickets_sold', $ticket_order['total_ticket_quantity']);
            }

            /*
             * Update the event stats
             */
            $event_stats = EventStats::updateOrCreate([
                'event_id' => $event_id,
                'date'     => DB::raw('CURRENT_DATE'),
            ]);
            $event_stats->increment('tickets_sold', $ticket_order['total_ticket_quantity']);

            if ($ticket_order['order_requires_payment']) {
                $event_stats->increment('sales_volume', $order->amount);
                $event_stats->increment('organiser_fees_volume', $order->organiser_booking_fee);
            }

            /*
             * Add the attendees
             */
            foreach ($ticket_order['tickets'] as $attendee_details) {
                /*
                 * Update ticket's quantity sold
                 */
                $ticket = Ticket::findOrFail($attendee_details['ticket']['id']);

                /*
                 * Update some ticket info
                 */
                $ticket->increment('quantity_sold', $attendee_details['qty']);
                $ticket->increment('sales_volume', ($attendee_details['ticket']['price'] * $attendee_details['qty']));
                $ticket->increment(
                    'organiser_fees_volume',
                    ($attendee_details['ticket']['organiser_booking_fee'] * $attendee_details['qty'])
                );

                $ticketPrice = $attendee_details['ticket']['price'];

                if (session('is_bring_friend')) {
            
                    $ticketPrice = bring_friend_price($ticketPrice);
                }

                /*
                 * Insert order items (for use in generating invoices)
                 */
                $orderItem = new OrderItem();
                $orderItem->title = $attendee_details['ticket']['title'];
                $orderItem->quantity = $attendee_details['qty'];
                $orderItem->order_id = $order->id;
                $orderItem->unit_price = $ticketPrice;
                $orderItem->unit_booking_fee = $attendee_details['ticket']['booking_fee'] + $attendee_details['ticket']['organiser_booking_fee'];
                $orderItem->save();

                /*
                 * Create the attendees
                 */
                for ($i = 0; $i < $attendee_details['qty']; $i++) {

                    $attendee = new Attendee();
                    if (isset($ticket_order['source'])) {
                        $attendee->first_name = sanitise($ticket_order['order_first_name']);
                        $attendee->last_name = sanitise($ticket_order['order_last_name']);
                        $attendee->email = sanitise($ticket_order['order_email']);
                        $lobbyFirstName = $ticket_order['order_first_name'];
                        $lobbycontact_no = $ticket_order['contact_no'];
                        $ref_id = $ticket_order['ref_id'];
                        $lobbyEmailId = $ticket_order['order_email'];
                        $lobbyLastName =     sanitise($ticket_order['order_last_name']);
                        if ($ticket->title == "Speaker Ticket") {
                            DB::table('Wo_Clickfunnel_Event_Purchase')->insert(
                                [
                                    'email_id' => $lobbyEmailId,
                                    'type' => 'speaker',
                                    'full_name' => $attendee->first_name . ' ' . $attendee->last_name,
                                    'first_name' => $attendee->first_name,
                                    'last_name' => $attendee->last_name,
                                    'mobile_no' => $lobbycontact_no,
                                    'user_id' => $ref_id,
                                    'amount' => $order->amount + $order->organiser_booking_fee,
                                    'event_id' => $event_id,
                                    'is_paid_points' => $ticket_order['is_paid_points'] ?? false,
                                    'points_deducted' => $ticket_order['points_deducted'] ?? null,
                                    'created' => date("Y-m-d h:i:s"),
                                    'referal_email' => $ticket_order['referal_email'] ?? null,
                                    'referal_first_name' => $ticket_order['referal_first_name'] ?? null,
                                    'referal_last_name' => $ticket_order['referal_last_name'] ?? null,
                                ]
                            );
                            DB::table('Wo_Clickfunnel_Event_SpeakerData')
                                ->where('event_id', $event_id)
                                ->where('email_id', $lobbyEmailId)
                                ->update(['status' => 'paid']);
                        } else {
                            try {
                                DB::table('Wo_Clickfunnel_Event_Purchase')->insert([
                                    'email_id' => $lobbyEmailId,
                                    'type' => 'general',
                                    'full_name' => $attendee->first_name . ' ' . $attendee->last_name,
                                    'first_name' => $attendee->first_name,
                                    'last_name' => $attendee->last_name,
                                    'mobile_no' => $lobbycontact_no,
                                    'user_id' => $ref_id,
                                    'amount' => $order->amount + $order->organiser_booking_fee,
                                    'event_id' => $event_id,
                                    'is_paid_points' => $ticket_order['is_paid_points'] ?? false,
                                    'points_deducted' => $ticket_order['points_deducted'] ?? null,
                                    'created' => date("Y-m-d h:i:s"),
                                    'is_member' => is_member(session('email')),
                                    'referal_email' => $ticket_order['referal_email'] ?? null,
                                    'referal_first_name' => $ticket_order['referal_first_name'] ?? null,
                                    'referal_last_name' => $ticket_order['referal_last_name'] ?? null,
                                ]);

                                if ($event->event_ttype == "Online") {
                                    $getZomm = $this->saveUserZoomEvent($lobbyFirstName, $lobbyEmailId, $lobbyLastName, $event_id);
                                } elseif ($event->event_ttype == "In-Person") {
                                    $eStartDate1 = $event->start_date;
                                    $finalDate = date('l, d-m-Y', strtotime($eStartDate1));
                                    $eStartDate = date('H:i', strtotime($eStartDate1));
                                    $eLocation = $event->location;
                                    $club222 = $event->club;
                                    $club1 = ucfirst(strtolower($club222));
                                    $data12 = array('name' => $lobbyFirstName, 'email' => $lobbyEmailId, 'club' => $club1, 'refno' => $order->order_reference, 'eLocation' => $eLocation, 'eStartDate' => $eStartDate, 'finalDate' => $finalDate);
                                    try {

                                        Mail::send('Emails.messageInPerson', $data12, function ($message) use ($lobbyEmailId) {
                                            $message->to($lobbyEmailId)->subject('Business Networking Event Confirmation');
                                            $message->from('admin@globalwomanclub.com', 'Admin');
                                        });
                                    } catch (\Exception $e) {
                                    }
                                } else {
                                    if ($order->amount == $event->mprice || $order->amount == $event->nmprice) {
                                        $getZomm = $this->saveUserZoomEvent($lobbyFirstName, $lobbyEmailId, $lobbyLastName, $event_id);
                                    } else {
                                        $eStartDate1 = $event->start_date;
                                        $finalDate = date('l, d-m-Y', strtotime($eStartDate1));
                                        $eStartDate = date('H:i', strtotime($eStartDate1));
                                        $eLocation = $event->location;
                                        $club222 = $event->club;
                                        $club1 = ucfirst(strtolower($club222));
                                        $data12 = array('name' => $lobbyFirstName, 'email' => $lobbyEmailId, 'club' => $club1, 'refno' => $order->order_reference, 'eLocation' => $eLocation, 'eStartDate' => $eStartDate, 'finalDate' => $finalDate);
                                        try {

                                            Mail::send('Emails.messageInPerson', $data12, function ($message) use ($lobbyEmailId) {
                                                $message->to($lobbyEmailId)->subject('Business Networking Event Confirmation');
                                                $message->from('admin@globalwomanclub.com', 'Admin');
                                            });
                                        } catch (\Exception $e) {
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                            }
                        }
                    } else {
                        $attendee->first_name = strip_tags($request_data["ticket_holder_first_name"][$i][$attendee_details['ticket']['id']]);
                        $attendee->last_name = strip_tags($request_data["ticket_holder_last_name"][$i][$attendee_details['ticket']['id']]);
                        $attendee->email = $request_data["ticket_holder_email"][$i][$attendee_details['ticket']['id']];
                        $lobbyFirstName = sanitise($request_data['order_first_name']);
                        $lobbyEmailId = sanitise($request_data['order_last_name']);
                    }
                    $attendee->event_id = $event_id;
                    $attendee->order_id = $order->id;
                    $attendee->ticket_id = $attendee_details['ticket']['id'];
                    $attendee->account_id = $event->account->id;
                    $attendee->reference_index = $attendee_increment;
                    $attendee->save();

                    // If bring a friend exists then also add to attendee and
                    // Event purchase
                    if (session('is_bring_friend') && session('bring_friend_details')) {

                        $friendDetails = session('bring_friend_details');
                        $attendee = new Attendee();
                        $attendee->first_name = $friendDetails['first_name'];
                        $attendee->last_name = $friendDetails['last_name'];
                        $attendee->email = $friendDetails['email'];
                        $attendee->event_id = $event_id;
                        $attendee->order_id = $order->id;
                        $attendee->ticket_id = $attendee_details['ticket']['id'];
                        $attendee->account_id = $event->account->id;
                        $attendee->reference_index = $attendee_increment + 1;
                        $attendee->save();
            
                        DB::table('Wo_Clickfunnel_Event_Purchase')->insert([
                            'email_id' => $attendee->email,
                            'type' => 'general',
                            'full_name' => $attendee->first_name . ' ' . $attendee->last_name,
                            'first_name' => $attendee->first_name,
                            'last_name' => $attendee->last_name,
                            'friend_email' => $lobbyEmailId,
                            'mobile_no' => $lobbycontact_no,
                            'user_id' => $ref_id,
                            'amount' => $order->amount + $order->organiser_booking_fee,
                            'event_id' => $event_id,
                            'created' => date("Y-m-d h:i:s"),
                        ]);
                    }
                   
                    $lobbyEventId = $event->lobby_event_id;
                    $s = $this->saveUserLobbyEvent($lobbyFirstName, $lobbyEmailId, $lobbyEventId);


                    /*
                     * Save the attendee's questions
                     */
                    foreach ($attendee_details['ticket']->questions as $question) {
                        $ticket_answer = isset($ticket_questions[$attendee_details['ticket']->id][$i][$question->id])
                            ? $ticket_questions[$attendee_details['ticket']->id][$i][$question->id]
                            : null;

                        if (is_null($ticket_answer)) {
                            continue;
                        }

                        /*
                         * If there are multiple answers to a question then join them with a comma
                         * and treat them as a single answer.
                         */
                        $ticket_answer = is_array($ticket_answer) ? implode(', ', $ticket_answer) : $ticket_answer;

                        if (!empty($ticket_answer)) {
                            QuestionAnswer::create([
                                'answer_text' => $ticket_answer,
                                'attendee_id' => $attendee->id,
                                'event_id'    => $event->id,
                                'account_id'  => $event->account->id,
                                'question_id' => $question->id
                            ]);
                        }
                    }

                    /* Keep track of total number of attendees */
                    $attendee_increment++;
                }
            }
        } catch (\Exception $e) {
            // Ids and the outcome only: an exception's message can carry the buyer's details.
            Log::error('Order not completed; rolled back', [
                'event_id' => $event_id,
                'error' => class_basename($e),
                'at' => basename($e->getFile()).':'.$e->getLine(),
            ]);
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Whoops! There was a problem processing your order. Please try again.'
            ]);
        }
        //save the order to the database
        DB::commit();
        //forget the order in the session
        session()->forget('ticket_order_' . $event->id);
        session()->forget('is_hybrid');
        session()->forget('stripeClientSecret.'.$event->club);
        session()->forget('stripePaymentIntentId.'.$event->club);
        session()->forget('stripePaymentIntentAccount.'.$event->club);
        session()->forget('stripePaymentIntentTagged.'.$event->club);
        session()->forget('stripeClientSecret');
        session()->forget('stripePaymentIntentId');
        session()->forget('is_vip');
        session()->forget('is_bring_friend');
        session()->forget('bring_friend_details');
        session()->forget('stripe_customer_id');
        session()->forget('stripe_email');
        session()->forget('speaking_slot_details');
        session()->forget('club_commission');
        session()->forget('club_unique_code');

        /*
         * Remove any tickets the user has reserved after they have been ordered for the user
         */
        ReservedTickets::where('session_id', '=', session()->getId())->delete();

        // Queue up some tasks - Emails to be sent, PDFs etc.
        Log::info('Firing the event');
        event(new OrderCompletedEvent($order));

        // Create tag only on production environment
        // This is to avoid creating tags in development and staging environments
        // as it can lead to a lot of unnecessary tags being created
        // and also to avoid hitting the GHL API limits in development and staging environments.
        if (env('APP_ENV') == 'production') {
            // Create GHL tag entry
            $eventDateTag = $event->club . '-' .  str_replace('-', '', substr($event->start_date, 0, 10));
            $ghl = new GoHighLevel();
            $locationId = $ghl->locationId;
            $ghlContact = [
                'firstName' => $order->first_name,
                'lastName' => $order->last_name,
                'email' => $order->email,
                'locationId' => $locationId,
            ];

            // If there is a phone number, add it to the contact
            if ($order->contact_no) {
                $ghlContact['phone'] = $order->contact_no;
            }

            Log::info('Creating or updating the buyer\'s GoHighLevel contact', ['order_id' => $order->id, 'event_id' => $event->id]);

            $contact = $ghl->createUpdateContact($ghlContact);

            // TODO: POST data to GHL so that the email could be send to user
            // Remove the sending of email from this platform and move it to GHL, so that we can have better email deliverability and also we can have better tracking of email opens and clicks in GHL.

            if (isset($contact['contact']['id'])) {
                // Add member visits
                if (is_member($order->email) == '1') {
                    $ghlMember = 'member';
                } else {
                    $ghlMember = 'nonmember';
                }

                $ghl->addTags($contact['contact']['id'], [$eventDateTag . '-purchased-' . $ghlMember]);

                // Purchase here
                $webhookJob = new \App\Jobs\HighLevelEventWebhook(
                    $event,
                    $order->email,
                    $event->club,
                    substr($event->start_date, 0, 10),
                    $event->title,
                    'booked_ticket'
                );
                dispatch($webhookJob);
            }
        }

        if ($return_json) {
            return response()->json([
                'status'      => 'success',
                'redirectUrl' => route('showOrderDetails', [
                    'is_embedded'     => $this->is_embedded,
                    'order_reference' => $order->order_reference,
                ]),
            ]);
        }

        return response()->redirectToRoute('showOrderDetails', [
            'is_embedded'     => $this->is_embedded,
            'order_reference' => $order->order_reference,
        ]);
    }

    public function saveUserLobbyEvent($lobbyFirstName, $lobbyEmailId, $lobbyEventId)
    {
        if ($this->lobbyEventSeconds >= self::LOBBY_EVENT_BUDGET) {
            Log::warning('curl call skipped', ['call' => 'saveuserevent', 'lobby_event_id' => $lobbyEventId]);

            return "test";
        }

        $started = now();
        $curl = curl_init();
        $url = "https://uhjpnqdcea.execute-api.eu-west-2.amazonaws.com/prod/saveuserevent?eventId=" . $lobbyEventId . "&firstName=" . $lobbyFirstName . "&emailId=" . $lobbyEmailId;
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => self::LOBBY_EVENT_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::LOBBY_EVENT_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));

        $response = curl_exec($curl);
        $this->lobbyEventSeconds += $started->diffInSeconds(now());

        // The order commits without this call. Its attendee rows hold what a replay would need;
        // the URL carries the buyer's name and email, so it stays out of the log.
        if ($response === false) {
            Log::warning('curl call failed', ['call' => 'saveuserevent', 'lobby_event_id' => $lobbyEventId, 'curl_errno' => curl_errno($curl)]);
        }

        $a = "test";
        curl_close($curl);
        return $a;
    }

    public function saveUserZoomEvent($lobbyFirstName, $lobbyEmailId, $lobbyLastName, $lobbyEventId)
    {
        $registrant_id = '';
        $join_url = '';
        if ($lobbyFirstName == "") {
            $lobbyFirstName = $lobbyEmailId;
        }
        if ($lobbyLastName == "") {
            $lobbyLastName = $lobbyFirstName;
        }
        $zoomUrl = DB::table('Wo_Clickfunnel_ZoomEvent_Data')->where('event_id', $lobbyEventId)->first();

        if ($zoomUrl == null) {
            return;
        }

        if ($lobbyEventId == "897") {
            $join_url = "https://furshmedia.stellartickets.com/events/global-woman-club-new-york";


            $affected = DB::table('Wo_Clickfunnel_Event_Purchase')
                ->where('event_id', $lobbyEventId)
                ->where('email_id', $lobbyEmailId)
                ->update(['zoom_url' => $join_url, 'registrant_id' => $join_url]);


            $data12 = array('name' => $lobbyFirstName, 'email' => $lobbyEmailId);
            try {
                Mail::send('Emails.messageAccra', $data12, function ($message) use ($lobbyEmailId) {
                    $message->to($lobbyEmailId)->subject('Global Woman Club Newyork Meeting information');
                    $message->from('admin@globalwomanclub.com', 'Admin');
                });
            } catch (\Exception $e) {
            }
        } else {

            $purchase = DB::table('Wo_Clickfunnel_Event_Purchase')
                ->where('event_id', $lobbyEventId)
                ->where('email_id', $lobbyEmailId)
                ->first();

            if ($purchase) {
                $client = new Client();
                $result = $client->post('https://globalwomanlounge.com/api/create-zoom', [
                    'json' => [
                        'refId' => $purchase->id,
                    ],
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
                        'Accept' => 'application/json'
                    ]
                ]);

                $resData = $result->getBody();

                $json = json_decode($resData, true);

                Log::info($json);
            }

            // https://globalwomanlounge.com/api/create-zoom
            // refId-Wo_Clickfunnel_Event_Purchase.id
            // $curl = curl_init();

            // curl_setopt_array($curl, array(
            //   CURLOPT_URL => "https://api.zoom.us/v2/meetings/".$zoomUrl->zoom_id."/registrants",
            //   CURLOPT_RETURNTRANSFER => true,
            //   CURLOPT_ENCODING => "",
            //   CURLOPT_MAXREDIRS => 10,
            //   CURLOPT_TIMEOUT => 0,
            //   CURLOPT_FOLLOWLOCATION => true,
            //   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //   CURLOPT_CUSTOMREQUEST => "POST",
            //   CURLOPT_POSTFIELDS =>"{\r\n    \"email\": \"$lobbyEmailId\",\r\n    \"first_name\": \"$lobbyFirstName\",\r\n    \"last_name\": \"$lobbyLastName\",\r\n    \"address\": \"\",\r\n    \"city\": \"\",\r\n    \"country\": \"\",\r\n    \"zip\": \"\",\r\n    \"state\": \"\",\r\n    \"phone\": \"\",\r\n    \"industry\": \"\",\r\n    \"org\": \"\",\r\n    \"job_title\": \"\",\r\n    \"purchasing_time_frame\": \"\",\r\n    \"role_in_purchase_process\": \"\",\r\n    \"no_of_employees\": \"\",\r\n    \"comments\": \"\",\r\n    \"custom_questions\": []\r\n}",
            //   CURLOPT_HTTPHEADER => array(
            // 	"Authorization: Bearer eyJzdiI6IjAwMDAwMSIsImFsZyI6IkhTNTEyIiwidiI6IjIuMCIasImtpZCI6IjYxMDMyN2ZhLWE0MTEtNGI2Mi1hYWFjLWNhYzUzMzlmY2E0ZiJ9.eyJhdWQiOiJodHRwczovL29hdXRoLnpvb20udXMiLCJ1aWQiOiI3RTA1cXJlOFNYT0lyNW1VbTNnVlnIiwidmVyIjo5LCJhdWlkIjoiNTZlYzIyZjZjNDE0OGE5M2Q1Mjg3ZTk1NTViNTQ0ODQiLCJuYmYiOjE2ODg1NjQ1NDAsImNvZGUiOiJtaHBDVU45alR1bS1WNldUem5TT1hRNFlQUjRYNHBzME0iLCJpc3MiOiJ6bTpjaWQ6Q0dZbTU5NjFTTW1SbzRzTzhDTG9nUSIsImdubyI6MCwiZXhwIjoxNjg4NTY4MTQwLCJ0eXBlIjozLCJpYXQiOjE2ODg1NjQ1NDAsImFpZCI6Iks3UUJUby1vUlh1c3phU01lSWpUREEifQ.jybrJH9oYdtNMZ_61InVsEyiS8_ll7fR_fLX9jCFXeveOl9s9anivHUw8DvJGrK_z4jVXteacJInU_2xDdMmbA",
            // 	"Content-Type: application/json",
            // 	"Cookie: cred=4F1EFECA0E7A3119794ECAE0762FCC5E"
            //   ),
            // ));

            // $response = curl_exec($curl);
            // curl_close($curl);
            // $json = json_decode($response, true);
            // Log::info($response);
            // if (isset($json['registrant_id'])) {
            //     $registrant_id = $json['registrant_id'];
            //     $join_url = $json['join_url'];
            //     $affected = DB::table('Wo_Clickfunnel_Event_Purchase')
            //             ->where('event_id', $lobbyEventId)
            //             ->where('email_id', $lobbyEmailId)
            //             ->update(['zoom_url' => $join_url,'registrant_id' => $registrant_id]);
            // }
        }
        $a = 'test';
        return $a;
    }



    /**
     * Show the order details page
     *
     * @param Request $request
     * @param $order_reference
     * @return \Illuminate\View\View
     */
    public function showOrderDetails(Request $request, $order_reference)
    {
        $source = session('source');

        $order = Order::where('order_reference', '=', $order_reference)->first();

        if (!$order) {
            abort(404);
        }

        $orderService = new OrderService($order->amount, $order->organiser_booking_fee, $order->event);
        $orderService->calculateFinalCosts();

        $data = [
            'order'        => $order,
            'orderService' => $orderService,
            'event'        => $order->event,
            'tickets'      => $order->event->tickets,
            'is_embedded'  => $this->is_embedded,
            'source'       => $source,
            'grand_total'  => $orderService->getGrandTotal(),
        ];

        $data['purchase_event'] = true;

        return view('Public.ViewEvent.EventPageViewOrder', $data);
    }

    /**
     * Shows the tickets for an order - either HTML or PDF
     *
     * @param Request $request
     * @param $order_reference
     * @return \Illuminate\View\View
     */
    public function showOrderTickets(Request $request, $order_reference)
    {
        $order = Order::where('order_reference', $order_reference)->first();

        if (!$order) {
            abort(404);
        }
        $images = [];
        $imgs = $order->event->images;
        foreach ($imgs as $img) {
            $eventImage = 'https://davxegdvkjbks.cloudfront.net/' . $img->image_path;
            $images[] = base64_encode($eventImage);
        }

        $data = [
            'order'     => $order,
            'event'     => $order->event,
            'tickets'   => $order->event->tickets,
            'attendees' => $order->attendees,
            'css'       => file_get_contents(public_path('assets/stylesheet/ticket.css')),
            'image'     => base64_encode(file_get_contents(public_path($order->event->organiser->full_logo_path))),
            'images'    => $images,
        ];

        if ($request->get('download') == '1') {

            $filePath = generate_pdf_receipt($order);

            if (file_exists($filePath)) {
                // Log::error("Cannot send actual ticket to : " . $order->email . " as ticket file does not exist on disk");
                // Header content type
                header("Content-type: application/pdf");
                header("Content-Length: " . filesize($filePath));

                // Send the file to the browser.
                readfile($filePath);
                return;
            }

            // $client = new Client();
            // $result = $client->get('https://api.globalwomanevents.com/tickets/UaF12282', [
            //     'headers' => [
            //         'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
            //         'Accept' => 'application/json',
            //     ]
            // ]);

            // $resData = $result->getBody();
            // $json = json_decode($resData, true);

            // https://api.globalwomanevents.com/tickets/UaF12282
            return PDF::html('Public.ViewEvent.Partials.PDFTicket', $data, 'Tickets');
        }

        return view('Public.ViewEvent.Partials.PDFTicket', $data);
    }
}
