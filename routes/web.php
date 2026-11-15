<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\EventAccessCodesController;
use App\Http\Controllers\EventAttendeesController;
use App\Http\Controllers\EventCheckInController;
use App\Http\Controllers\EventCheckoutController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventCustomizeController;
use App\Http\Controllers\EventDashboardController;
use App\Http\Controllers\EventOrdersController;
use App\Http\Controllers\EventPromoteController;
use App\Http\Controllers\EventSurveyController;
use App\Http\Controllers\EventTicketsController;
use App\Http\Controllers\EventViewController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ManageAccountController;
use App\Http\Controllers\OrganiserController;
use App\Http\Controllers\OrganiserCustomizeController;
use App\Http\Controllers\OrganiserDashboardController;
use App\Http\Controllers\OrganiserEventsController;
use App\Http\Controllers\OrganiserViewController;
use App\Http\Controllers\RemindersController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserLoginController;
use App\Http\Controllers\UserLogoutController;
use App\Models\EventPurchase;
use App\Models\Order;
use Illuminate\Support\Facades\Route;

Route::get('/purchase-speaker-ticket', [EventViewController::class, 'speakerBuyNow']);
Route::post('/saveSpeakerData', [EventViewController::class, 'saveSpeakerData']);
Route::post('saveUserData', [LandingController::class, 'saveUserDataEvent']);
Route::post('saveUserDataLondon', [LandingController::class, 'saveUserDataLondon']);
Route::any('saveContactGHL', [LandingController::class, 'saveContactGHL']);
Route::any('getGHToken', [LandingController::class, 'getGHLToken']);
Route::any('checkCoupon', [LandingController::class, 'checkCoupon'])->name('check.coupon');
Route::post('set-user-session', [SessionController::class, 'setUserSession'])->name('setUserSession');
Route::post('postBuyNow', [EventViewController::class, 'postBuyNow']);
Route::post('postBuyNow1', [EventViewController::class, 'postBuyNow1']);
Route::post('postBuyNow2', [EventViewController::class, 'postBuyNow2']);
Route::post('postBuyMobileUpdate', [EventViewController::class, 'postBuyMobileUpdate']);
Route::post('postBuyCouponCodeForm', [EventViewController::class, 'postBuyCouponCodeForm']);
Route::post('referral-respond', [EventViewController::class, 'referralRespond'])->name('referral.respond');

Route::middleware(['throttle:event_throttle'])->group(function () {
    // Use this in the future to get the list of clubs
    Route::get(
        '{club}',
        [EventViewController::class, 'clubPage']
    )->whereIn('club', clubs());

    Route::get('/online', [EventViewController::class, 'online'])->name('online');

    // Route::get('/forum', [EventViewController::class, 'showFeaturedEvents'])->defaults('club', 'forum');
    Route::get('/landing', [LandingController::class, 'showLandingPage']);
    Route::get('/clubs', [LandingController::class, 'showClubsPage']);
    Route::get('/events', [LandingController::class, 'showEventsPage']);
    Route::get('/masterclass', [LandingController::class, 'showMasterclassPage']);
    Route::get('/mastermind', [LandingController::class, 'showMastermindPage']);
    Route::get('/orders', [LandingController::class, 'showOrdersPage']);
    Route::get('/clubs/{club}', [LandingController::class, 'showClubEventPage']);
    Route::get('/club/{id}', [EventViewController::class, 'showEvent'])->name('event.dynamic');
});

// Route::get('/london/{id}', 'EventView1Controller@showClubsEventsDynamic');
// Route::post('saveUserDataLondonEvent', 'EventView1Controller@saveUserDataLondonEvent');

Route::group(
    ['prefix' => LaravelLocalization::setLocale()],
    function () {

        Route::any('/logout', [UserLogoutController::class, 'doLogout'])->name('logout');

        Route::group([], function () {

            /*
         * Login
         */
            Route::get(
                '/login',
                [UserLoginController::class, 'showLogin']
            )->name('login');

            Route::post(
                '/login',
                [UserLoginController::class, 'postLogin']
            );

            Route::get(
                '/logincallback',
                [UserLoginController::class, 'callback']
            )->name('logincallback');

            /*
         * Forgot password
         */
            Route::get(
                'login/forgot-password',
                [RemindersController::class, 'getRemind']
            )->name('forgotPassword');

            Route::post(
                'login/forgot-password',
                [RemindersController::class, 'postRemind']
            )->name('postForgotPassword');

            /*
         * Reset Password
         */
            Route::get(
                'login/reset-password/{token}',
                [RemindersController::class, 'getReset']
            )->name('password.reset');

            Route::post(
                'login/reset-password',
                [RemindersController::class, 'postReset']
            )->name('postResetPassword');
        });

        /*
     * Public organiser page routes
     */
        Route::group(['prefix' => 'o'], function () {

            Route::get(
                '/{organiser_id}/{organier_slug?}',
                [OrganiserViewController::class, 'showOrganiserHome']
            )->name('showOrganiserHome');
        });

        /*
     * Public event page routes
     */
        Route::group(['prefix' => 'e'], function () {

            Route::get(
                '/{event_id}/calendar.ics',
                [EventViewController::class, 'showCalendarIcs']
            )->name('downloadCalendarIcs');

            /*
         * Takes any slug, so the retired /embed URL is refused here. Without this it renders the event.
         */
            Route::get(
                '/{event_id}/{event_slug?}',
                [EventViewController::class, 'showEventHome']
            )->where('event_slug', '(?!embed$)[^/]+')->name('showEventPage');

            Route::post(
                '/{event_id}/contact_organiser',
                [EventViewController::class, 'postContactOrganiser']
            )->name('postContactOrganiser');

            Route::post(
                '/{event_id}/show_hidden',
                [EventViewController::class, 'postShowHiddenTickets']
            )->name('postShowHiddenTickets');

            /*
         * Used for previewing designs in the backend. Doesn't log page views etc.
         */
            Route::get(
                '/{event_id}/preview',
                [EventViewController::class, 'showEventHomePreview']
            )->name('showEventPagePreview');

            Route::post(
                '{event_id}/checkout/',
                [EventCheckoutController::class, 'postValidateTickets']
            )->name('postValidateTickets');

            Route::post(
                '{event_id}/checkout/validate',
                [EventCheckoutController::class, 'postValidateOrder']
            )->name('postValidateOrder');

            Route::get(
                '{event_id}/checkout/payment',
                [EventCheckoutController::class, 'showEventPayment']
            )->name('showEventPayment');

            Route::get(
                '{event_id}/checkout/create',
                [EventCheckoutController::class, 'showEventCheckout']
            )->name('showEventCheckout');

            Route::get(
                '{event_id}/checkout/success',
                [EventCheckoutController::class, 'showEventCheckoutPaymentReturn']
            )->name('showEventCheckoutPaymentReturn');

            Route::post(
                '{event_id}/checkout/create',
                [EventCheckoutController::class, 'postCreateOrder']
            )->name('postCreateOrder');
        });

        /*
     * Public view order routes
     */
        Route::get(
            'order/{order_reference}',
            [EventCheckoutController::class, 'showOrderDetails']
        )->name('showOrderDetails');

        Route::get(
            'order/{order_reference}/tickets',
            [EventCheckoutController::class, 'showOrderTickets']
        )->name('showOrderTickets');

        /*
     * Backend routes
     */
        Route::group(['middleware' => ['auth']], function () {

            /*
         * Edit User
         */
            Route::group(['prefix' => 'user'], function () {

                Route::get(
                    '/',
                    [UserController::class, 'showEditUser']
                )->name('showEditUser');

                Route::post(
                    '/',
                    [UserController::class, 'postEditUser']
                )->name('postEditUser');
            });

            /*
         * Manage account
         */
            Route::group(['prefix' => 'account'], function () {

                Route::get(
                    '/',
                    [ManageAccountController::class, 'showEditAccount']
                )->name('showEditAccount');

                Route::post(
                    '/',
                    [ManageAccountController::class, 'postEditAccount']
                )->name('postEditAccount');

                Route::post(
                    '/edit_payment',
                    [ManageAccountController::class, 'postEditAccountPayment']
                )->name('postEditAccountPayment');

                Route::post(
                    'invite_user',
                    [ManageAccountController::class, 'postInviteUser']
                )->name('postInviteUser');
            });

            Route::get(
                '/',
                [LandingController::class, 'showLandingPage']
            )->name('showSelectOrganiser');


            Route::get(
                'select_organisers',
                [OrganiserController::class, 'showSelectOrganiser']
            )->name('showSelectOrganisers');

            /*
         * Organiser routes
         */
            Route::group(['prefix' => 'organiser'], function () {

                Route::get(
                    '{organiser_id}/dashboard',
                    [OrganiserDashboardController::class, 'showDashboard']
                )->name('showOrganiserDashboard');

                Route::get(
                    '{organiser_id}/events',
                    [OrganiserEventsController::class, 'showEvents']
                )->name('showOrganiserEvents');

                Route::get(
                    '{organiser_id}/customize',
                    [OrganiserCustomizeController::class, 'showCustomize']
                )->name('showOrganiserCustomize');

                Route::post(
                    '{organiser_id}/customize',
                    [OrganiserCustomizeController::class, 'postEditOrganiser']
                )->name('postEditOrganiser');

                Route::get(
                    'create',
                    [OrganiserController::class, 'showCreateOrganiser']
                )->name('showCreateOrganiser');

                Route::post(
                    'create',
                    [OrganiserController::class, 'postCreateOrganiser']
                )->name('postCreateOrganiser');

                Route::post(
                    '{organiser_id}/page_design',
                    [OrganiserCustomizeController::class, 'postEditOrganiserPageDesign']
                )->name('postEditOrganiserPageDesign');
            });

            /*
         * Events dashboard
         */
            Route::group(['prefix' => 'events'], function () {

                /*
             * ----------
             * Create Event
             * ----------
             */
                Route::get(
                    '/create',
                    [EventController::class, 'showCreateEvent']
                )->name('showCreateEvent');

                Route::post(
                    '/create',
                    [EventController::class, 'postCreateEvent']
                )->name('postCreateEvent');
            });

            /*
         * Upload event images
         */
            Route::post(
                '/upload_image',
                [EventController::class, 'postUploadEventImage']
            )->name('postUploadEventImage');

            /*
         * Event management routes
         */
            Route::group(['prefix' => 'event'], function () {

                /*
             * Dashboard
             */
                Route::get(
                    '{event_id}/dashboard/',
                    [EventDashboardController::class, 'showDashboard']
                )->name('showEventDashboard');

                Route::get(
                    '{event_id}/',
                    [EventDashboardController::class, 'redirectToDashboard']
                );

                Route::get(
                    '{event_id}/go_live',
                    [EventController::class, 'makeEventLive']
                )->name('MakeEventLive');

                /*
             * -------
             * Tickets
             * -------
             */
                Route::get(
                    '{event_id}/tickets/',
                    [EventTicketsController::class, 'showTickets']
                )->name('showEventTickets');

                Route::get(
                    '{event_id}/tickets/edit/{ticket_id}',
                    [EventTicketsController::class, 'showEditTicket']
                )->name('showEditTicket');

                Route::post(
                    '{event_id}/tickets/edit/{ticket_id}',
                    [EventTicketsController::class, 'postEditTicket']
                )->name('postEditTicket');

                Route::get(
                    '{event_id}/tickets/create',
                    [EventTicketsController::class, 'showCreateTicket']
                )->name('showCreateTicket');

                Route::post(
                    '{event_id}/tickets/create',
                    [EventTicketsController::class, 'postCreateTicket']
                )->name('postCreateTicket');

                Route::post(
                    '{event_id}/tickets/delete',
                    [EventTicketsController::class, 'postDeleteTicket']
                )->name('postDeleteTicket');

                Route::post(
                    '{event_id}/tickets/pause',
                    [EventTicketsController::class, 'postPauseTicket']
                )->name('postPauseTicket');

                Route::post(
                    '{event_id}/tickets/order',
                    [EventTicketsController::class, 'postUpdateTicketsOrder']
                )->name('postUpdateTicketsOrder');

                /*
             * -------
             * Attendees
             * -------
             */
                Route::get(
                    '{event_id}/attendees/',
                    [EventAttendeesController::class, 'showAttendees']
                )->name('showEventAttendees');

                Route::get(
                    '{event_id}/attendees/message',
                    [EventAttendeesController::class, 'showMessageAttendees']
                )->name('showMessageAttendees');

                Route::post(
                    '{event_id}/attendees/message',
                    [EventAttendeesController::class, 'postMessageAttendees']
                )->name('postMessageAttendees');

                Route::get(
                    '{attendee_id}/attendees/single_message',
                    [EventAttendeesController::class, 'showMessageAttendee']
                )->name('showMessageAttendee');

                Route::post(
                    '{attendee_id}/attendees/single_message',
                    [EventAttendeesController::class, 'postMessageAttendee']
                )->name('postMessageAttendee');

                Route::get(
                    '{attendee_id}/attendees/resend_ticket',
                    [EventAttendeesController::class, 'showResendTicketToAttendee']
                )->name('showResendTicketToAttendee');

                Route::post(
                    '{attendee_id}/attendees/resend_ticket',
                    [EventAttendeesController::class, 'postResendTicketToAttendee']
                )->name('postResendTicketToAttendee');

                Route::get(
                    '{event_id}/attendees/invite',
                    [EventAttendeesController::class, 'showInviteAttendee']
                )->name('showInviteAttendee');

                Route::post(
                    '{event_id}/attendees/invite',
                    [EventAttendeesController::class, 'postInviteAttendee']
                )->name('postInviteAttendee');

                Route::get(
                    '{event_id}/attendees/import',
                    [EventAttendeesController::class, 'showImportAttendee']
                )->name('showImportAttendee');

                Route::post(
                    '{event_id}/attendees/import',
                    [EventAttendeesController::class, 'postImportAttendee']
                )->name('postImportAttendee');

                Route::get(
                    '{event_id}/attendees/print',
                    [EventAttendeesController::class, 'showPrintAttendees']
                )->name('showPrintAttendees');

                Route::get(
                    '{event_id}/attendees/{attendee_id}/export_ticket',
                    [EventAttendeesController::class, 'showExportTicket']
                )->name('showExportTicket');

                Route::get(
                    '{event_id}/attendees/{attendee_id}/ticket',
                    [EventAttendeesController::class, 'showAttendeeTicket']
                )->name('showAttendeeTicket');

                Route::get(
                    '{event_id}/attendees/export/{export_as?}',
                    [EventAttendeesController::class, 'showExportAttendees']
                )->name('showExportAttendees');

                Route::get(
                    '{event_id}/attendees/{attendee_id}/edit',
                    [EventAttendeesController::class, 'showEditAttendee']
                )->name('showEditAttendee');

                Route::post(
                    '{event_id}/attendees/{attendee_id}/edit',
                    [EventAttendeesController::class, 'postEditAttendee']
                )->name('postEditAttendee');

                Route::get(
                    '{event_id}/attendees/{attendee_id}/cancel',
                    [EventAttendeesController::class, 'showCancelAttendee']
                )->name('showCancelAttendee');

                Route::post(
                    '{event_id}/attendees/{attendee_id}/cancel',
                    [EventAttendeesController::class, 'postCancelAttendee']
                )->name('postCancelAttendee');

                /*
             * -------
             * Orders
             * -------
             */
                Route::get(
                    '{event_id}/orders/',
                    [EventOrdersController::class, 'showOrders']
                )->name('showEventOrders');

                Route::get(
                    'order/{order_id}',
                    [EventOrdersController::class, 'manageOrder']
                )->name('showManageOrder');

                Route::post(
                    'order/{order_id}/resend',
                    [EventOrdersController::class, 'resendOrder']
                )->name('resendOrder');

                Route::get(
                    'order/{order_id}/show/edit',
                    [EventOrdersController::class, 'showEditOrder']
                )->name('showEditOrder');

                Route::post(
                    'order/{order_id}/edit',
                    [EventOrdersController::class, 'postEditOrder']
                )->name('postOrderEdit');

                Route::get(
                    'order/{order_id}/cancel',
                    [EventOrdersController::class, 'showCancelOrder']
                )->name('showCancelOrder');

                Route::post(
                    'order/{order_id}/cancel',
                    [EventOrdersController::class, 'postCancelOrder']
                )->name('postCancelOrder');

                Route::post(
                    'order/{order_id}/mark_payment_received',
                    [EventOrdersController::class, 'postMarkPaymentReceived']
                )->name('postMarkPaymentReceived');

                Route::get(
                    '{event_id}/orders/export/{export_as?}',
                    [EventOrdersController::class, 'showExportOrders']
                )->name('showExportOrders');

                Route::get(
                    '{event_id}/orders/message',
                    [EventOrdersController::class, 'showMessageOrder']
                )->name('showMessageOrder');

                Route::post(
                    '{event_id}/orders/message',
                    [EventOrdersController::class, 'postMessageOrder']
                )->name('postMessageOrder');

                /*
             * -------
             * Edit Event
             * -------
             */
                Route::post(
                    '{event_id}/customize',
                    [EventController::class, 'postEditEvent']
                )->name('postEditEvent');

                /*
             * -------
             * Customize Design etc.
             * -------
             */
                Route::get(
                    '{event_id}/customize',
                    [EventCustomizeController::class, 'showCustomize']
                )->name('showEventCustomize');

                Route::get(
                    '{event_id}/customize/{tab?}',
                    [EventCustomizeController::class, 'showCustomize']
                )->name('showEventCustomizeTab');

                Route::post(
                    '{event_id}/customize/order_page',
                    [EventCustomizeController::class, 'postEditEventOrderPage']
                )->name('postEditEventOrderPage');

                Route::post(
                    '{event_id}/customize/design',
                    [EventCustomizeController::class, 'postEditEventDesign']
                )->name('postEditEventDesign');

                Route::post(
                    '{event_id}/customize/ticket_design',
                    [EventCustomizeController::class, 'postEditEventTicketDesign']
                )->name('postEditEventTicketDesign');

                Route::post(
                    '{event_id}/customize/social',
                    [EventCustomizeController::class, 'postEditEventSocial']
                )->name('postEditEventSocial');

                Route::post(
                    '{event_id}/customize/fees',
                    [EventCustomizeController::class, 'postEditEventFees']
                )->name('postEditEventFees');

                /*
             * -------
             * Event Access Codes page
             * -------
             */
                Route::get(
                    '{event_id}/access_codes',
                    [EventAccessCodesController::class, 'show']
                )->name('showEventAccessCodes');

                Route::get(
                    '{event_id}/access_codes/create',
                    [EventAccessCodesController::class, 'showCreate']
                )->name('showCreateEventAccessCode');

                Route::post(
                    '{event_id}/access_codes/create',
                    [EventAccessCodesController::class, 'postCreate']
                )->name('postCreateEventAccessCode');

                Route::post(
                    '{event_id}/access_codes/{access_code_id}/delete',
                    [EventAccessCodesController::class, 'postDelete']
                )->name('postDeleteEventAccessCode');

                /*
             * -------
             * Event Survey page
             * -------
             */
                Route::get(
                    '{event_id}/surveys',
                    [EventSurveyController::class, 'showEventSurveys']
                )->name('showEventSurveys');

                Route::get(
                    '{event_id}/question/create',
                    [EventSurveyController::class, 'showCreateEventQuestion']
                )->name('showCreateEventQuestion');

                Route::post(
                    '{event_id}/question/create',
                    [EventSurveyController::class, 'postCreateEventQuestion']
                )->name('postCreateEventQuestion');

                Route::get(
                    '{event_id}/question/{question_id}',
                    [EventSurveyController::class, 'showEditEventQuestion']
                )->name('showEditEventQuestion');

                Route::post(
                    '{event_id}/question/{question_id}',
                    [EventSurveyController::class, 'postEditEventQuestion']
                )->name('postEditEventQuestion');

                Route::post(
                    '{event_id}/question/delete/{question_id}',
                    [EventSurveyController::class, 'postDeleteEventQuestion']
                )->name('postDeleteEventQuestion');

                Route::get(
                    '{event_id}/question/{question_id}/answers',
                    [EventSurveyController::class, 'showEventQuestionAnswers']
                )->name('showEventQuestionAnswers');

                Route::post(
                    '{event_id}/questions/update_order',
                    [EventSurveyController::class, 'postUpdateQuestionsOrder']
                )->name('postUpdateQuestionsOrder');

                Route::get(
                    '{event_id}/answers/export/{export_as?}',
                    [EventSurveyController::class, 'showExportAnswers']
                )->name('showExportAnswers');

                Route::post(
                    '{event_id}/question/{question_id}/enable',
                    [EventSurveyController::class, 'postEnableQuestion']
                )->name('postEnableQuestion');


                /*
             * -------
             * Check In App
             * -------
             */
                Route::get(
                    '{event_id}/check_in',
                    [EventCheckInController::class, 'showCheckIn']
                )->name('showCheckIn');

                Route::post(
                    '{event_id}/check_in/search',
                    [EventCheckInController::class, 'postCheckInSearch']
                )->name('postCheckInSearch');

                Route::post(
                    '{event_id}/check_in/',
                    [EventCheckInController::class, 'postCheckInAttendee']
                )->name('postCheckInAttendee');

                Route::post(
                    '{event_id}/qrcode_check_in',
                    [EventCheckInController::class, 'postCheckInAttendeeQr']
                )->name('postQRCodeCheckInAttendee');

                Route::post(
                    '{event_id}/confirm_order_tickets/{order_id}',
                    [EventCheckInController::class, 'confirmOrderTicketsQr']
                )->name('confirmCheckInOrderTickets');


                /*
             * -------
             * Promote
             * -------
             */
                Route::get(
                    '{event_id}/promote',
                    [EventPromoteController::class, 'showPromote']
                )->name('showEventPromote');
            });
        });

        Route::get(
            '/',
            [LandingController::class, 'showLandingPage']
        )->name('index');
    }
);
Route::get('/', [LandingController::class, 'showLandingPage'])
    ->middleware(['throttle:event_throttle']);

Route::post('/update-club', [EventViewController::class, 'updateClubTemplate'])->name('update.club.data');
Route::get('/club-content/{club}', [EventViewController::class, 'getClubContent'])->name('get.club.content');
Route::post('/club-content/{club}/update', [EventViewController::class, 'updateClubContent'])->name('update.club.content');
Route::post('/club-content/{club}/files', [EventViewController::class, 'updateClubContentFiles'])->name('update.club.files');
// POST so it carries the CSRF token: it deletes a Lounge club_images row.
Route::post('/delete-image', [EventViewController::class, 'deleteImage'])->name('delete.image');

Route::get('logs-xsdsdsodhd-dshdsyewds-dsdshdsjjdsj', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index'])->middleware('auth.basic');

use Illuminate\Support\Facades\View;

// Called server to server by the Lounge (TicketsController): 200 means resent, anything else failed.
// Unauthenticated, and its 200/404 shows whether an email booked an event, so it is throttled
// (the 'resend-email' limiter in AppServiceProvider).
Route::get('resend-email', function () {

    $order = Order::query()
        ->where('email', request('email'))
        ->where('event_id', request('event_id'))
        ->first();

    if ($order) {
        Mail::send(new \App\Mail\EventBooked($order));
        return [];
        return new \App\Mail\EventBooked($order);
    }

    // Try to check if its a guest order
    $guest = EventPurchase::query()
        ->where('email_id', request('email'))
        ->where('event_id', request('event_id'))
        ->where('is_purchased', false)
        ->first();

    if ($guest) {
        // $order = new Order();
        // $order->event_id = $guest->event_id;
    }

    abort(404);
})->middleware('throttle:resend-email');

Route::get('register-url', function () {
    session(['last_url' => route('login.redirect')]);
    return redirect('https://lobby.globalwomanclub.com/index.php?link1=login-event&last_url=http://lobby.globalwomanclub.com/oauth?app_id=' . config('services.lobby_app.app_id'));
    // http://lobby.globalwomanclub.com/oauth?app_id=77d26d549bb4fa49cc62
})->name('register.url');

Route::get('lobby-login-redirect', function () {
    // This will close the window
    session(['last_url' => null]);
    return View::make('reload');
})->name('login.redirect');

// https://globalwomansummit.com/ticket-2026
Route::get('summit-ticket', function () {
    store_club_commission(request('c'));
    // TODO change me
    $data = [
        'online' => request()->root() .'/club/3455?c=' .session('club_unique_code'),
        'general' => request()->root() .'/club/3456?c=' .session('club_unique_code'),
        'vip' => request()->root() .'/club/3457?c=' .session('club_unique_code'),

        'elite' => request()->root() .'/club/3458?c=' .session('club_unique_code'),
        'gala' => request()->root() .'/club/3459?c=' .session('club_unique_code'),
        'oneday' => request()->root() .'/club/3460?c=' .session('club_unique_code'),
    ];
    return View::make('summit-ticket', $data);
})->name('summit.ticket');
