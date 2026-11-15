<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\EventPurchase;
use App\Models\EmailSchedule;

class DeleteScheduledWhenPurchased extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchased:scheduled-delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'DeleteScheduledWhenPurchased';

    /**
     * Execute the console command.s
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info('DeleteScheduledWhenPurchased command started');

        $purchases = EventPurchase::query()
            ->where('created', 'LIKE', now()->format('Y-m-d') . '%')
            ->limit(100)
            ->get();

        foreach ($purchases as $purchase) {

            // Delete the scheduled email if the purchase is made
            EmailSchedule::where('email', $purchase->email_id)
                ->whereIn('type', [1, 2, 3, -3, -2, -1, 31, 32, 34, 36])
                ->delete();

            Log::info('Done!');
        }
    }
}
