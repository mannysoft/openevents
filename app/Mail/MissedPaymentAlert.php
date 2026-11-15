<?php

namespace App\Mail;

use App\Services\EventPaymentLookup;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tells a person about event payments that have no booking record (events:check-missed-payments):
 * events-site payments with no order, and Lounge app or web payments with no Lounge purchase row.
 */
class MissedPaymentAlert extends Mailable
{
    /** Stripe currencies with no minor unit. */
    private const ZERO_DECIMAL = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

    /**
     * @param  array<int, array<string, mixed>>  $missed
     * @param  array<int, array<string, mixed>>  $failures
     */
    public function __construct(
        public array $missed,
        public array $failures,
        public int $windowStart,
        public int $windowEnd,
    ) {}

    public function envelope(): Envelope
    {
        $count = count($this->missed);
        $lounge = count($this->loungeRows());

        if ($count === 0) {
            $subject = sprintf('Missed-payment check: %d Stripe account%s could not be checked', count($this->failures), count($this->failures) === 1 ? '' : 's');
        } elseif ($lounge === 0) {
            $subject = sprintf('Action needed: %d paid event booking%s with no order', $count, $count === 1 ? '' : 's');
        } elseif ($lounge === $count) {
            $subject = sprintf('Action needed: %d paid Lounge booking%s with no ticket record', $count, $count === 1 ? '' : 's');
        } else {
            $subject = sprintf('Action needed: %d paid event bookings with no order or ticket record', $count);
        }

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            text: 'Emails.missed-payment-alert',
            with: [
                'rows' => array_map(fn (array $m) => $this->presentable($m), $this->siteRows()),
                'loungeRows' => array_map(fn (array $m) => $this->presentable($m), $this->loungeRows()),
                'failures' => $this->failures,
                'checkedFrom' => self::utc($this->windowStart),
                'checkedUntil' => self::utc($this->windowEnd),
            ],
        );
    }

    /**
     * What the alert calls a Lounge booking, by where it was made.
     */
    public static function loungeLabel(?string $source): string
    {
        return match ($source) {
            EventPaymentLookup::LOUNGE_APP => 'Lounge app booking',
            EventPaymentLookup::LOUNGE_WEB => 'Lounge web booking (app.globalwomanlounge.com)',
            EventPaymentLookup::LOUNGE_APP_CARD => 'Lounge app booking (card taken in an older app version)',
            default => 'Lounge booking, app or web (its Stripe checkout could not be identified)',
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function siteRows(): array
    {
        return array_values(array_filter($this->missed, fn (array $m) => ($m['kind'] ?? 'events_site') !== 'lounge'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loungeRows(): array
    {
        return array_values(array_filter($this->missed, fn (array $m) => ($m['kind'] ?? 'events_site') === 'lounge'));
    }

    /**
     * @param  array<string, mixed>  $missed
     * @return array<string, mixed>
     */
    private function presentable(array $missed): array
    {
        $paidAt = Carbon::createFromTimestamp($missed['paid_at'], 'UTC');

        return $missed + [
            'amount_text' => self::money($missed['amount'], $missed['currency']),
            'paid_utc' => $paidAt->format('D j M Y, H:i').' UTC',
            'paid_uk' => $paidAt->copy()->setTimezone('Europe/London')->format('H:i').' UK time',
            'club_text' => $missed['clubs'] ? implode(', ', $missed['clubs']) : 'unknown',
            'account_text' => $missed['account'] === 'platform' ? 'platform account' : $missed['account'],
            'source_text' => self::loungeLabel($missed['source'] ?? null),
            'friend_text' => trim(implode(' ', array_filter([$missed['friend_name'] ?? null, $missed['friend_email'] ?? null]))),
        ];
    }

    public static function money(int $minor, string $currency): string
    {
        $currency = strtoupper($currency);

        if (in_array($currency, self::ZERO_DECIMAL, true)) {
            return number_format($minor).' '.$currency;
        }

        return number_format($minor / 100, 2).' '.$currency;
    }

    private static function utc(int $timestamp): string
    {
        return Carbon::createFromTimestamp($timestamp, 'UTC')->format('D j M Y, H:i').' UTC';
    }
}
