<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Stripe\Charge;
use Stripe\Checkout\Session;
use Stripe\Exception\ExceptionInterface as StripeException;
use Stripe\PaymentIntent;

/**
 * Finds event payments that succeeded in Stripe but have no booking record.
 *
 * Events site: the order is written only in showEventCheckoutPaymentReturn(), when the buyer's
 * browser comes back with its session; there is no webhook. orders.transaction_id holds the charge
 * id (the PaymentIntent's latest_charge), so a succeeded events-site PaymentIntent whose charge is
 * not in orders.transaction_id is a payment with no booking.
 *
 * Lounge app and Lounge web: the Lounge's PaymentController::storeTicket writes a row in
 * Wo_Clickfunnel_Event_Purchase (this database; the Lounge reaches it as mysql_events) only when
 * the app or the web page calls POST /api/v1/tickets after Stripe hands the buyer back. Nothing
 * else records it: the Lounge's club webhook ignores these checkout sessions. The row keeps neither
 * the PaymentIntent nor the charge id, only the event, the member's email (email_id) and user id,
 * so a payment is matched to a row by event and buyer email, one row per payment.
 *
 * It cannot create the order or the row: completeOrder() rebuilds an order from the checkout
 * session (tickets, attendee details, discounts, bring-a-friend guest), none of which is in Stripe,
 * and a Lounge row needs the member's account, which Stripe does not name.
 */
class MissedPaymentScanner
{
    /** The Lounge's event purchase table, in the events database. */
    public const LOUNGE_PURCHASES = 'Wo_Clickfunnel_Event_Purchase';

    /**
     * How long before the payment a Lounge purchase row may be dated and still count for it.
     * storeTicket writes created_at in the Lounge's timezone, and the events site writes `created`
     * with date('Y-m-d h:i:s'), a 12-hour clock in Europe/London. 36 hours absorbs both and still
     * stops a booking from an earlier week covering a new payment.
     */
    private const LOUNGE_ROW_SLACK_SECONDS = 36 * 3600;

    public function __construct(private EventPaymentLookup $lookup) {}

    /**
     * @return array{
     *     missed: array<int, array<string, mixed>>,
     *     failures: array<int, array{account: string, clubs: string[], error: string, message: string, label?: string}>,
     *     checked: string[],
     *     counts: array<string, int>,
     *     from: int,
     *     until: int
     * }
     */
    public function scan(CarbonInterface $now, int $lookbackHours, int $graceMinutes): array
    {
        $from = $now->getTimestamp() - $lookbackHours * 3600;
        $until = $now->getTimestamp() - $graceMinutes * 60;

        $result = [
            'missed' => [],
            'failures' => [],
            'checked' => [],
            'counts' => [
                'succeeded' => 0, 'lounge' => 0, 'lounge_recorded' => 0, 'other' => 0,
                'too_recent' => 0, 'refunded' => 0, 'matched' => 0,
            ],
            'from' => $from,
            'until' => $until,
        ];

        // Lounge bookings from every account, checked together at the end, so that one purchase
        // row never covers two payments.
        $lounge = [];

        foreach ($this->lookup->accounts() as $key => $account) {
            // One account failing (a club that revoked access returns PermissionException) must
            // not stop the others. It is reported as not checked.
            try {
                $intents = $this->succeededIntents($account['account'], $from, $until);
            } catch (StripeException $e) {
                $result['failures'][] = [
                    'account' => $key,
                    'clubs' => $account['clubs'],
                    'error' => class_basename($e),
                    'message' => $e->getMessage(),
                ];

                continue;
            }

            $result['checked'][] = $key;

            $candidates = [];
            $other = [];

            foreach ($intents as $intent) {
                $result['counts']['succeeded']++;

                $metadata = $intent->metadata ? $intent->metadata->toArray() : [];

                if (EventPaymentLookup::isLoungeEventMetadata($metadata)) {
                    $lounge[$intent->id] = [
                        'intent' => $intent,
                        'metadata' => $metadata,
                        'session' => null,
                        'account' => $key,
                        'stripe_account' => $account['account'],
                        'clubs' => $account['clubs'],
                    ];

                    continue;
                }

                if (! EventPaymentLookup::isEventsSitePayment($intent)) {
                    $result['counts']['other']++;
                    $other[$intent->id] = true;

                    continue;
                }

                $charge = $intent->latest_charge instanceof Charge ? $intent->latest_charge : null;

                // Paid less than the grace period ago: the buyer may still be on her way back.
                if ($charge && ($charge->created ?? 0) > $until) {
                    $result['counts']['too_recent']++;

                    continue;
                }

                // Fully refunded already: nobody needs adding.
                if ($charge && ($charge->refunded ?? false)) {
                    $result['counts']['refunded']++;

                    continue;
                }

                $candidates[] = $this->describe($intent, $charge, $key, $account['clubs']);
            }

            $withOrder = $this->chargesWithOrders(array_filter(array_column($candidates, 'charge')));

            foreach ($candidates as $candidate) {
                if ($candidate['charge'] !== null && isset($withOrder[$candidate['charge']])) {
                    $result['counts']['matched']++;

                    continue;
                }

                $result['missed'][] = $this->withLocalContext($candidate);
            }

            // A Lounge checkout charged on the platform (its club is not onboarded) carries its
            // metadata on the checkout session only; the PaymentIntent has none and was counted
            // as "other" above. Only the sessions find it.
            if ($account['account'] === null) {
                $this->addPlatformLoungeSessions($lounge, $other, $key, $from, $until, $result);
            }
        }

        $this->checkLoungeBookings($lounge, $until, $result);

        return $result;
    }

    /**
     * Succeeded PaymentIntents created in the window, every page, with the charge expanded.
     *
     * @return PaymentIntent[]
     */
    private function succeededIntents(?string $account, int $from, int $until): array
    {
        $page = $this->lookup->stripe()->paymentIntents->all(
            [
                'created' => ['gte' => $from, 'lte' => $until],
                'limit' => 100,
                'expand' => ['data.latest_charge'],
            ],
            $account ? ['stripe_account' => $account] : null
        );

        $intents = [];

        foreach ($page->autoPagingIterator() as $intent) {
            if ($intent->status === 'succeeded') {
                $intents[] = $intent;
            }
        }

        return $intents;
    }

    /**
     * @param  string[]  $clubs
     * @return array<string, mixed>
     */
    private function describe(PaymentIntent $intent, ?Charge $charge, string $account, array $clubs): array
    {
        $metadata = $intent->metadata ? $intent->metadata->toArray() : [];
        $chargeMetadata = $charge && $charge->metadata ? $charge->metadata->toArray() : [];
        $billing = $charge->billing_details ?? null;

        $chargeId = $charge ? $charge->id : (is_string($intent->latest_charge) ? $intent->latest_charge : null);

        $email = $metadata['email']
            ?? $chargeMetadata['email']
            ?? ($billing->email ?? null)
            ?? ($charge->receipt_email ?? null)
            ?? ($intent->receipt_email ?? null);

        $name = trim(($chargeMetadata['first_name'] ?? '').' '.($chargeMetadata['last_name'] ?? ''));

        if ($name === '') {
            $name = $billing->name ?? null;
        }

        return [
            'kind' => 'events_site',
            'payment_intent' => $intent->id,
            'charge' => $chargeId,
            'account' => $account,
            'clubs' => $clubs,
            'event_id' => (string) $metadata['event_id'],
            'event_title' => $metadata['event_title'] ?? null,
            'event_date' => $metadata['event_date'] ?? null,
            'amount' => (int) ($intent->amount_received ?: $intent->amount),
            'currency' => strtoupper((string) $intent->currency),
            'paid_at' => (int) ($charge->created ?? $intent->created),
            'email' => $email ?: null,
            'name' => $name ?: null,
        ];
    }

    /**
     * @param  string[]  $chargeIds
     * @return array<string, true>
     */
    private function chargesWithOrders(array $chargeIds): array
    {
        $found = [];

        foreach (array_chunk(array_values(array_unique($chargeIds)), 500) as $chunk) {
            foreach (DB::table('orders')->whereIn('transaction_id', $chunk)->pluck('transaction_id') as $id) {
                $found[$id] = true;
            }
        }

        return $found;
    }

    /**
     * Adds what the events database knows: the event's title and club when Stripe lacks them,
     * and any order this email already has for the event (then this may be a second payment).
     *
     * @param  array<string, mixed>  $missed
     * @return array<string, mixed>
     */
    private function withLocalContext(array $missed): array
    {
        $event = DB::table('events')->where('id', $missed['event_id'])->first(['title', 'club', 'start_date']);

        if ($event) {
            if (empty($missed['event_title']) || $missed['event_title'] === 'N/A') {
                $missed['event_title'] = $event->title;
            }

            if (empty($missed['event_date']) || $missed['event_date'] === 'N/A') {
                $missed['event_date'] = $event->start_date;
            }

            if ($missed['clubs'] === [] && ! empty($event->club)) {
                $missed['clubs'] = [$event->club];
            }
        }

        $missed['existing_order'] = null;

        if ($missed['email']) {
            $missed['existing_order'] = DB::table('orders')
                ->where('event_id', $missed['event_id'])
                ->whereRaw('LOWER(email) = ?', [strtolower($missed['email'])])
                ->value('order_reference');
        }

        return $missed;
    }

    /**
     * Lounge checkouts on the platform account, found through their sessions.
     *
     * @param  array<string, array<string, mixed>>  $lounge
     * @param  array<string, true>  $other  platform PaymentIntents already counted as "other"
     * @param  array<string, mixed>  $result
     */
    private function addPlatformLoungeSessions(array &$lounge, array $other, string $key, int $from, int $until, array &$result): void
    {
        try {
            $page = $this->lookup->stripe()->checkout->sessions->all([
                'created' => ['gte' => $from, 'lte' => $until],
                'status' => 'complete',
                'limit' => 100,
                'expand' => ['data.payment_intent.latest_charge'],
            ]);

            $sessions = iterator_to_array($page->autoPagingIterator(), false);
        } catch (StripeException $e) {
            $result['failures'][] = [
                'account' => $key.'-lounge-checkouts',
                'clubs' => [],
                'error' => class_basename($e),
                'message' => $e->getMessage(),
                'label' => 'platform account, Lounge checkouts only',
            ];

            return;
        }

        foreach ($sessions as $session) {
            $metadata = $session->metadata ? $session->metadata->toArray() : [];
            $intent = $session->payment_intent;

            if (! $intent instanceof PaymentIntent
                || $intent->status !== 'succeeded'
                || ! EventPaymentLookup::isLoungeEventMetadata($metadata)
                || isset($lounge[$intent->id])) {
                continue;
            }

            if (isset($other[$intent->id])) {
                $result['counts']['other']--;
            } else {
                $result['counts']['succeeded']++;
            }

            $lounge[$intent->id] = [
                'intent' => $intent,
                'metadata' => $metadata,
                'session' => $session,
                'account' => $key,
                'stripe_account' => null,
                'clubs' => [],
            ];
        }
    }

    /**
     * Matches each Lounge booking to a paid purchase row for its event and buyer email; the rest
     * are missed.
     *
     * @param  array<string, array<string, mixed>>  $bookings
     * @param  array<string, mixed>  $result
     */
    private function checkLoungeBookings(array $bookings, int $until, array &$result): void
    {
        $candidates = [];

        foreach ($bookings as $booking) {
            $result['counts']['lounge']++;

            $intent = $booking['intent'];
            $charge = $intent->latest_charge instanceof Charge ? $intent->latest_charge : null;

            // The app or the page records the booking straight after Stripe hands the buyer back.
            if ($charge && ($charge->created ?? 0) > $until) {
                $result['counts']['too_recent']++;

                continue;
            }

            if ($charge && ($charge->refunded ?? false)) {
                $result['counts']['refunded']++;

                continue;
            }

            $candidates[] = $this->describeLounge($booking, $charge);
        }

        if ($candidates === []) {
            return;
        }

        try {
            $rows = $this->loungePurchaseRows(array_values(array_unique(array_column($candidates, 'event_id'))));
        } catch (\Throwable $e) {
            // Without the rows every Lounge booking would look missed. Say the check could not run
            // rather than alert on all of them.
            $result['failures'][] = [
                'account' => 'lounge-purchase-records',
                'clubs' => [],
                'error' => class_basename($e),
                'message' => $e->getMessage(),
                'label' => 'Lounge purchase records ('.self::LOUNGE_PURCHASES.'), so no Lounge app or web booking was checked',
            ];

            return;
        }

        // Oldest payment first: when one buyer paid twice and one row exists, the row covers the
        // first payment and the second is reported.
        usort($candidates, fn (array $a, array $b) => [$a['paid_at'], $a['payment_intent']] <=> [$b['paid_at'], $b['payment_intent']]);

        foreach ($candidates as $candidate) {
            if ($this->claimPurchaseRow($rows, $candidate)) {
                $result['counts']['lounge_recorded']++;

                continue;
            }

            // Not under any email on the payment. The checkout session holds the email typed at
            // Stripe, and says whether the app or the website took the payment.
            if (! $candidate['session_read']) {
                $candidate = $this->withCheckoutSession($candidate);

                if ($this->claimPurchaseRow($rows, $candidate)) {
                    $result['counts']['lounge_recorded']++;

                    continue;
                }
            }

            $missed = $this->withLocalContext($candidate);
            $missed['lounge_places'] = $this->placesFor($rows, $candidate);
            $result['missed'][] = $missed;
        }
    }

    /**
     * @param  array<string, mixed>  $booking
     * @return array<string, mixed>
     */
    private function describeLounge(array $booking, ?Charge $charge): array
    {
        /** @var PaymentIntent $intent */
        $intent = $booking['intent'];
        $metadata = $booking['metadata'];
        $billing = $charge->billing_details ?? null;

        $emails = self::emails([
            $metadata['email'] ?? null,
            $billing->email ?? null,
            $charge->receipt_email ?? null,
            $intent->receipt_email ?? null,
        ]);

        $name = trim(($metadata['first_name'] ?? '').' '.($metadata['last_name'] ?? ''));
        $friendName = trim(($metadata['friend_first_name'] ?? '').' '.($metadata['friend_last_name'] ?? ''));

        // An app version that took the card itself (createPaymentIntent) has no checkout session.
        $isCheckout = array_key_exists('from_app', $metadata);

        $candidate = [
            'kind' => 'lounge',
            'source' => $isCheckout ? EventPaymentLookup::LOUNGE_UNKNOWN : EventPaymentLookup::LOUNGE_APP_CARD,
            'payment_intent' => $intent->id,
            'charge' => $charge ? $charge->id : (is_string($intent->latest_charge) ? $intent->latest_charge : null),
            'session' => null,
            'session_read' => ! $isCheckout,
            'session_error' => null,
            'account' => $booking['account'],
            'stripe_account' => $booking['stripe_account'],
            'clubs' => $booking['clubs'],
            'event_id' => (string) $metadata['event_id'],
            'event_title' => $metadata['event_title'] ?? null,
            'event_date' => $metadata['event_date'] ?? null,
            'ticket_type' => $metadata['ticket_type'] ?? null,
            'bring_friend' => ($metadata['is_bring_friend'] ?? null) === 'true',
            'friend_name' => $friendName !== '' ? $friendName : null,
            'friend_email' => EventPaymentLookup::normaliseEmail($metadata['friend_email'] ?? null),
            'amount' => (int) ($intent->amount_received ?: $intent->amount),
            'currency' => strtoupper((string) $intent->currency),
            'paid_at' => (int) ($charge->created ?? $intent->created),
            'emails' => $emails,
            'email' => $emails[0] ?? null,
            'name' => $name !== '' ? $name : ($billing->name ?? null),
        ];

        return $booking['session'] ? $this->withSession($candidate, $booking['session']) : $candidate;
    }

    /**
     * Reads the checkout session that took this payment (on the same account).
     *
     * @param  array<string, mixed>  $candidate
     * @return array<string, mixed>
     */
    private function withCheckoutSession(array $candidate): array
    {
        $candidate['session_read'] = true;

        try {
            $found = $this->lookup->stripe()->checkout->sessions->all(
                ['payment_intent' => $candidate['payment_intent'], 'limit' => 1],
                $candidate['stripe_account'] ? ['stripe_account' => $candidate['stripe_account']] : null
            );
        } catch (StripeException $e) {
            // Still reported: the payment has no row whatever the session says.
            $candidate['session_error'] = class_basename($e);

            return $candidate;
        }

        $session = $found->data[0] ?? null;

        return $session instanceof Session ? $this->withSession($candidate, $session) : $candidate;
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return array<string, mixed>
     */
    private function withSession(array $candidate, Session $session): array
    {
        $details = $session->customer_details ?? null;

        $candidate['emails'] = self::emails(array_merge($candidate['emails'], [
            $details->email ?? null,
            $session->customer_email ?? null,
        ]));
        $candidate['email'] = $candidate['emails'][0] ?? null;
        $candidate['name'] = $candidate['name'] ?: ($details->name ?? null);
        $candidate['source'] = EventPaymentLookup::loungeSource($session->success_url ?? null);
        $candidate['session'] = $session->id;
        $candidate['session_read'] = true;

        return $candidate;
    }

    /**
     * Every Lounge purchase row for these events that is not deleted, keyed "event_id|email",
     * oldest first.
     *
     * @param  string[]  $eventIds
     * @return array<string, array<int, array{paid: bool, at: ?int, claimed: bool}>>
     */
    private function loungePurchaseRows(array $eventIds): array
    {
        $rows = [];

        foreach (array_chunk($eventIds, 500) as $chunk) {
            $found = DB::table(self::LOUNGE_PURCHASES)
                ->whereIn('event_id', $chunk)
                ->whereNull('deleted_at')
                ->get(['event_id', 'email_id', 'amount', 'is_purchased', 'created_at', 'created']);

            foreach ($found as $row) {
                $email = strtolower(trim((string) $row->email_id));

                if ($email === '') {
                    continue;
                }

                $rows[((string) $row->event_id).'|'.$email][] = [
                    // A free registration (amount 0) and a guest invite or unfinished registration
                    // (is_purchased 0) are not a record of a payment.
                    'paid' => (float) $row->amount > 0 && (int) $row->is_purchased !== 0,
                    'at' => self::rowTime($row->created_at ?? $row->created ?? null),
                    'claimed' => false,
                ];
            }
        }

        foreach ($rows as &$list) {
            usort($list, fn (array $a, array $b) => ($a['at'] ?? PHP_INT_MIN) <=> ($b['at'] ?? PHP_INT_MIN));
        }

        unset($list);

        return $rows;
    }

    /**
     * Takes the oldest unclaimed paid row for this payment's event under any of its emails, dated
     * no earlier than the slack before the payment. A row with no date counts.
     *
     * @param  array<string, array<int, array{paid: bool, at: ?int, claimed: bool}>>  $rows
     * @param  array<string, mixed>  $candidate
     */
    private function claimPurchaseRow(array &$rows, array $candidate): bool
    {
        $floor = $candidate['paid_at'] - self::LOUNGE_ROW_SLACK_SECONDS;

        foreach ($candidate['emails'] as $email) {
            $key = $candidate['event_id'].'|'.$email;

            foreach ($rows[$key] ?? [] as $i => $row) {
                if ($row['paid'] && ! $row['claimed'] && ($row['at'] === null || $row['at'] >= $floor)) {
                    $rows[$key][$i]['claimed'] = true;

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * How many rows (paid, free or guest, at any date) this buyer already has at the event.
     *
     * @param  array<string, array<int, array<string, mixed>>>  $rows
     * @param  array<string, mixed>  $candidate
     */
    private function placesFor(array $rows, array $candidate): int
    {
        $count = 0;

        foreach ($candidate['emails'] as $email) {
            $count += count($rows[$candidate['event_id'].'|'.$email] ?? []);
        }

        return $count;
    }

    /**
     * @param  array<int, mixed>  $values
     * @return string[]
     */
    private static function emails(array $values): array
    {
        $emails = [];

        foreach ($values as $value) {
            $email = is_string($value) ? EventPaymentLookup::normaliseEmail($value) : null;

            if ($email !== null && ! in_array($email, $emails, true)) {
                $emails[] = $email;
            }
        }

        return $emails;
    }

    private static function rowTime(mixed $value): ?int
    {
        if (! is_string($value) || $value === '' || str_starts_with($value, '0000-00-00')) {
            return null;
        }

        try {
            $time = Carbon::parse($value, 'UTC')->getTimestamp();
        } catch (\Throwable) {
            return null;
        }

        return $time > 0 ? $time : null;
    }
}
