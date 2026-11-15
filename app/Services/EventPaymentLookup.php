<?php

namespace App\Services;

use App\Models\LoungeClub;
use Illuminate\Support\Facades\Log;
use Stripe\Charge;
use Stripe\Exception\ExceptionInterface as StripeException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

/**
 * Finds events-site payments in Stripe.
 *
 * StripeGateway::getClientSecret() creates the PaymentIntent on the club's connected account
 * when the club is onboarded and takes a fee, and on the platform account otherwise. Every one
 * carries metadata event_id, event_title and event_date, and, since this change, the buyer's
 * email as soon as the site knows it.
 */
class EventPaymentLookup
{
    /** Account key used for the platform's own Stripe account. */
    public const PLATFORM = 'platform';

    /**
     * Metadata keys the Lounge app and the Lounge web checkout put on their own event payments
     * (PaymentController::createPaymentIntent, StripeProductTrait::createEventSession). The events
     * site never sets them. Those bookings are recorded in the Lounge, not in `orders`.
     *
     * `from_app` does NOT mean the app: createEventSession sets it on every Lounge checkout, the
     * Flutter app's and the React web app's alike. Only the checkout session's success_url tells
     * them apart (see loungeSource()).
     */
    public const LOUNGE_MARKERS = ['from_app', 'ticket_type', 'user_id'];

    /** A booking made in the Flutter app (Stripe Checkout in its WebView). */
    public const LOUNGE_APP = 'app';

    /** A booking made on the Lounge website (app.globalwomanlounge.com, the React app). */
    public const LOUNGE_WEB = 'web';

    /** An older app version that took the card itself (PaymentController::createPaymentIntent). */
    public const LOUNGE_APP_CARD = 'app_card';

    /** A Lounge booking whose checkout session could not be read or recognised. */
    public const LOUNGE_UNKNOWN = 'unknown';

    public function stripe(): StripeClient
    {
        return new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Every account an events-site checkout can charge: the platform first, then each onboarded
     * club's connected account, with the club slugs that use it.
     *
     * @return array<string, array{account: ?string, clubs: string[]}>
     */
    public function accounts(): array
    {
        $accounts = [self::PLATFORM => ['account' => null, 'clubs' => []]];

        $clubs = LoungeClub::query()
            ->where('is_onboarded', 1)
            ->whereNotNull('stripe_account_id')
            ->where('stripe_account_id', '!=', '')
            ->orderBy('slug')
            ->get(['slug', 'stripe_account_id']);

        foreach ($clubs as $club) {
            $id = $club->stripe_account_id;
            $accounts[$id] ??= ['account' => $id, 'clubs' => []];
            $accounts[$id]['clubs'][] = $club->slug;
        }

        return $accounts;
    }

    /**
     * The accounts one club's checkout can charge: the platform, plus the club's connected
     * account when it is onboarded. getClientSecret() falls back to the platform when the fee
     * works out at zero, so both are searched.
     *
     * @return array<int, ?string>
     */
    public function accountsForClub(?string $clubSlug): array
    {
        $accounts = [null];

        if ($clubSlug) {
            $club = LoungeClub::where('slug', $clubSlug)->first();

            if ($club && $club->is_onboarded && $club->stripe_account_id) {
                $accounts[] = $club->stripe_account_id;
            }
        }

        return $accounts;
    }

    /**
     * True for a PaymentIntent the events-site checkout created: it names an event and carries
     * the event_date key getClientSecret() always sets, and none of the Lounge's own keys.
     */
    public static function isEventsSitePayment(PaymentIntent $intent): bool
    {
        $metadata = $intent->metadata ? $intent->metadata->toArray() : [];

        $eventId = $metadata['event_id'] ?? null;

        if ($eventId === null || $eventId === '' || $eventId === 'N/A') {
            return false;
        }

        if (! array_key_exists('event_date', $metadata)) {
            return false;
        }

        foreach (self::LOUNGE_MARKERS as $key) {
            if (array_key_exists($key, $metadata)) {
                return false;
            }
        }

        return true;
    }

    /**
     * True for a Lounge booking (app or web) of an events-site event: it names an event and
     * carries at least one of the Lounge's own keys. The booking itself lives in the Lounge's
     * purchase table (Wo_Clickfunnel_Event_Purchase), never in `orders`.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function isLoungeEventMetadata(array $metadata): bool
    {
        $eventId = $metadata['event_id'] ?? null;

        if ($eventId === null || $eventId === '' || $eventId === 'N/A') {
            return false;
        }

        return array_intersect(self::LOUNGE_MARKERS, array_keys($metadata)) !== [];
    }

    /**
     * Where a Lounge checkout was made, from its session's success_url:
     *
     * - the Flutter app passes no success_url, so createEventSession's default
     *   `{app.url}/payment/success?session_id=…` is used, and the app's WebView watches for it;
     * - the React web app (EventDetailModal, EventDetailsPage) sends `{origin}/events…?success=true…`.
     */
    public static function loungeSource(?string $successUrl): string
    {
        if ($successUrl === null || $successUrl === '') {
            return self::LOUNGE_UNKNOWN;
        }

        $path = (string) parse_url($successUrl, PHP_URL_PATH);

        if (str_contains($path, '/payment/success')) {
            return self::LOUNGE_APP;
        }

        if (preg_match('#^/events(/|$)#', $path)) {
            return self::LOUNGE_WEB;
        }

        return self::LOUNGE_UNKNOWN;
    }

    /**
     * The email in the form Stripe metadata holds it, or null when it is not a valid address.
     */
    public static function normaliseEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * The id of an earlier succeeded PaymentIntent for this buyer and event, or null.
     *
     * This finds a buyer who paid and never came back, so has no order and no email on the
     * charge. Stripe search can lag by about a minute (up to an hour in an outage). A Stripe error
     * is logged and does not block the checkout.
     *
     * Never counted: the PaymentIntent of the checkout in progress (her email is put on it before
     * she pays, and postCreateOrder can run after it has succeeded, so counting it would refuse
     * her own booking), and a payment refunded in full (she may book again).
     */
    public function succeededPaymentFor(?string $email, $eventId, ?string $clubSlug): ?string
    {
        $email = self::normaliseEmail($email);

        if ($email === null || $eventId === null || $eventId === '') {
            return null;
        }

        $query = sprintf(
            "status:'succeeded' AND metadata['event_id']:'%s' AND metadata['email']:'%s'",
            self::quote((string) $eventId),
            self::quote($email)
        );

        $current = StripeGateway::currentIntentId($clubSlug);

        foreach ($this->accountsForClub($clubSlug) as $account) {
            try {
                $found = $this->stripe()->paymentIntents->search(
                    ['query' => $query, 'limit' => 10, 'expand' => ['data.latest_charge']],
                    $account ? ['stripe_account' => $account] : null
                );
            } catch (StripeException $e) {
                Log::warning('Already-purchased check: PaymentIntent search failed', [
                    'event_id' => (string) $eventId,
                    'account' => $account ?? self::PLATFORM,
                    'error' => class_basename($e),
                ]);

                continue;
            }

            foreach ($found->data as $intent) {
                if ($intent->id === $current) {
                    continue;
                }

                $charge = $intent->latest_charge ?? null;

                if ($charge instanceof Charge && ($charge->refunded ?? false)) {
                    continue;
                }

                return $intent->id;
            }
        }

        return null;
    }

    /**
     * Escapes a value for a single-quoted Stripe search string.
     */
    private static function quote(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }
}
