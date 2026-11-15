<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LoungeClub;
use App\Models\Order;
use App\Services\StripeGateway;
use Illuminate\Support\Facades\Log;

class StripeCommissionTransfer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:commission-transfer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer commission for Stripe payments';

    /**
     * Execute the console command.s
     *
     * @return mixed
     */
    public function handle()
    {
        $eventIds = [
            3455,
            3456,
            3457,
            3458,
            3459,
            3460,
        ];

        $orders = Order::query()
            ->where('amount', '!=', 0)
            ->whereNotNull('club_commission')
            ->whereNotNull('transaction_id')
            ->where('is_transferred', false)
            ->whereIn('event_id', $eventIds)
            ->get();

        foreach ($orders as $order) {
            $this->info('Processing order ID: ' . $order->id);

            $club = LoungeClub::where('slug', $order->club_commission)->first();

            if ($club || $order->club_commission == 'emma.walton') {
                $stripeGateway = new StripeGateway();
                if ($club?->is_onboarded || $order->club_commission == 'emma.walton') {

                    // $charge = $stripeGateway->getPaymentIntent(request('payment_intent'));

                    $stripeAccountId = $club?->stripe_account_id;

                    if ($order->club_commission == 'emma.walton') {
                        if (config('app.env') === 'production') {
                            $stripeAccountId = 'acct_1TPJjqAlG3xbhRpr';
                        } else {
                            // Local for testing, we can use the same account as Emma Walton
                            // https://dashboard.stripe.com/acct_1RVMpwPFNZtrH0sz/test/connect/accounts/acct_1S8I43PBH1wmDUB2/activity
                            $stripeAccountId = 'acct_1S8I43PBH1wmDUB2';
                        }
                    }

                    $isAccountExists = $stripeGateway->retrieveAccount($stripeAccountId);

                    // Try to check the connected account if still valid
                    // If some reason the account is not valid, we will not use it
                    // Use the platform account instead
                    if ($isAccountExists != false) {
                        // $event = Event::findOrFail($event_id);

                        $transfer = $stripeGateway->transfer(
                            // $charge->latest_charge,
                            $order->transaction_id, // this is the charge ID
                            $stripeAccountId,
                            '30% Commision from ' . $order->event->title . ' ticket sales',
                            $order
                            );

                        if ($transfer) {
                            $order->is_transferred = true;
                            $order->save();

                            $this->info('Commission transferred for order ID: ' . $order->id);
                        } else {
                            $this->error('Failed to transfer commission for order ID: ' . $order->id);
                        }
                    }
                }
            }
        }

        Log::info('Stripe commission transfer completed');

        $this->comment('Done!');
    }
}
