<?php

namespace App\Services;

use App\Gateways\EventGateway;

/**
 * Who may pay for an order with Lobby points.
 *
 * Paying with points writes to the Lobby's own database: a row in its `points` table, and a new
 * balance on the Wo_Users row that matches the order's email. Until 12 Nov the Lobby step at
 * registration kept anonymous visitors away from that, by accident. Now it needs both:
 *  - LOBBY_POINTS_REDEMPTION switched on. It is off unless set, and stays off while the Lobby is
 *    offline;
 *  - a session holding a completed Lobby sign-in, whose verified email is the order's email.
 *
 * The number of points still comes from the request. A balance check on the server is owed
 * before this is switched on again.
 */
class PointsRedemptionGuard
{
    /** Session values that look like a sign-in and are not one: see EventGateway::createUser(). */
    private const NOT_A_SIGN_IN = ['emailExists', EventGateway::NO_LOBBY_ACCOUNT];

    public static function allows(?string $orderEmail): bool
    {
        if (! config('services.lobby_points.redemption')) {
            return false;
        }

        $token = session('access_token');

        if (! is_string($token) || $token === '' || in_array($token, self::NOT_A_SIGN_IN, true)) {
            return false;
        }

        // 'events_email' is set only by a completed Lobby sign-in (UserLoginController::callback
        // and EventViewController), never from a form.
        $signedInEmail = EventPaymentLookup::normaliseEmail(session('events_email'));

        return $signedInEmail !== null && $signedInEmail === EventPaymentLookup::normaliseEmail($orderEmail);
    }
}
