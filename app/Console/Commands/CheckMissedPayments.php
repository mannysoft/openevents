<?php

namespace App\Console\Commands;

use App\Mail\MissedPaymentAlert;
use App\Services\MissedPaymentScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Finds event payments that succeeded in Stripe but never became a booking (an events-site
 * order, or a Lounge app or web purchase row), and emails the tech address once per payment.
 *
 * Alert only: neither record can be rebuilt from Stripe (see MissedPaymentScanner).
 */
class CheckMissedPayments extends Command
{
    protected $signature = 'events:check-missed-payments
        {--dry-run : List what would be reported; send nothing and remember nothing}';

    protected $description = 'Alert on paid event bookings with no order or Lounge purchase record (the booking was never saved after Stripe)';

    private const REPORTED_PREFIX = 'missed-payment-reported:';

    private const UNCHECKED_PREFIX = 'missed-payment-unchecked-reported:';

    public function handle(MissedPaymentScanner $scanner): int
    {
        $result = $scanner->scan(
            now(),
            max(1, (int) config('missed_payments.lookback_hours')),
            max(0, (int) config('missed_payments.grace_minutes'))
        );

        foreach ($result['failures'] as $failure) {
            Log::warning('events:check-missed-payments could not check a Stripe account', [
                'account' => $failure['account'],
                'clubs' => $failure['clubs'],
                'error' => $failure['error'],
            ]);
        }

        $new = array_values(array_filter(
            $result['missed'],
            fn (array $missed) => ! Cache::has(self::REPORTED_PREFIX.$missed['payment_intent'])
        ));

        foreach ($new as $missed) {
            if (($missed['kind'] ?? null) === 'lounge') {
                Log::error('Paid Lounge event booking with no purchase record', [
                    'payment_intent' => $missed['payment_intent'],
                    'account' => $missed['account'],
                    'event_id' => $missed['event_id'],
                    'source' => $missed['source'],
                ]);

                continue;
            }

            Log::error('Paid event booking with no order', [
                'payment_intent' => $missed['payment_intent'],
                'account' => $missed['account'],
                'event_id' => $missed['event_id'],
            ]);
        }

        // An account that cannot be checked is listed in every alert that goes out, and gets an
        // alert of its own at most once a day, so it is never silent and never every 10 minutes.
        $failuresToReport = $new !== []
            ? $result['failures']
            : array_values(array_filter(
                $result['failures'],
                fn (array $failure) => ! Cache::has(self::UNCHECKED_PREFIX.$failure['account'])
            ));

        $this->summarise($result, $new);

        if ($new === [] && $failuresToReport === []) {
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->line('Dry run: no email sent, nothing remembered.');

            return self::SUCCESS;
        }

        $recipients = $this->recipients();

        if ($recipients === []) {
            Log::error('events:check-missed-payments has no valid alert address (missed_payments.alert_to)');
            $this->error('No valid alert address configured; nothing sent.');

            return self::FAILURE;
        }

        try {
            Mail::to($recipients)->send(new MissedPaymentAlert($new, $failuresToReport, $result['from'], $result['until']));
        } catch (\Throwable $e) {
            // Not remembered, so the next run tries again.
            Log::error('events:check-missed-payments could not send its alert', ['error' => class_basename($e), 'message' => $e->getMessage()]);
            $this->error('Alert not sent: '.class_basename($e));

            return self::FAILURE;
        }

        $remember = now()->addDays(max(1, (int) config('missed_payments.remember_days')));

        foreach ($new as $missed) {
            Cache::put(self::REPORTED_PREFIX.$missed['payment_intent'], now()->getTimestamp(), $remember);
        }

        foreach ($failuresToReport as $failure) {
            Cache::put(self::UNCHECKED_PREFIX.$failure['account'], now()->getTimestamp(), now()->addDay());
        }

        $this->info('Alert sent.');

        return self::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function recipients(): array
    {
        $addresses = array_map('trim', explode(',', (string) config('missed_payments.alert_to')));

        return array_values(array_filter($addresses, fn (string $address) => filter_var($address, FILTER_VALIDATE_EMAIL)));
    }

    /**
     * One line per finding, with Stripe ids only: no buyer details in the console or the logs.
     *
     * @param  array<string, mixed>  $result
     * @param  array<int, array<string, mixed>>  $new
     */
    private function summarise(array $result, array $new): void
    {
        $counts = $result['counts'];

        $this->line(sprintf(
            'Checked %d account(s); %d could not be checked. Succeeded payments: %d (events site matched to an order: %d; Lounge app/web: %d, with a Lounge purchase record: %d; other: %d; too recent: %d; refunded: %d).',
            count($result['checked']),
            count($result['failures']),
            $counts['succeeded'],
            $counts['matched'],
            $counts['lounge'],
            $counts['lounge_recorded'],
            $counts['other'],
            $counts['too_recent'],
            $counts['refunded']
        ));

        $this->line(sprintf('With no order: %d (%d not reported before).', count($result['missed']), count($new)));

        foreach ($new as $missed) {
            $this->line(sprintf(
                '  NEW %s event %s on %s%s',
                $missed['payment_intent'],
                $missed['event_id'],
                $missed['account'],
                ($missed['kind'] ?? null) === 'lounge' ? ' ('.MissedPaymentAlert::loungeLabel($missed['source']).')' : ''
            ));
        }

        foreach ($result['failures'] as $failure) {
            $this->line(isset($failure['label'])
                ? sprintf('  COULD NOT CHECK %s: %s', $failure['label'], $failure['error'])
                : sprintf('  COULD NOT CHECK %s (%s): %s', $failure['account'], implode(', ', $failure['clubs']) ?: 'platform', $failure['error']));
        }
    }
}
