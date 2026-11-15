<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Event;

use App\Models\EventPurchase;
use App\Models\VisitLog;

class UpdatePurchaseVisits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchase-visits:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the purchase visits for events';

    /**
     * Execute the console command.s
     *
     * @return mixed
     */
    public function handle()
    {
        $event = Event::find(2639);

        $puchases = EventPurchase::query()
            ->where('event_id', 2639)
            ->where('amount', '>', 0)
            ->limit(100)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($puchases as $purchase) {
            $visitLog = VisitLog::firstOrNew([
                'event_id' => $purchase->event_id,
                'email_id' => $purchase->email_id
            ]);

            $visitLog->first_name = $purchase->first_name ?? $purchase->full_name;
            $visitLog->last_name = $purchase->last_name ?? $purchase->full_name;
            $visitLog->mobile_no = $purchase->mobile_no;
            $visitLog->created = $purchase->created;
            $visitLog->date_created = substr($purchase->created, 0, 10);
            $visitLog->club = $event->club;
            $visitLog->save();
        }

        $this->comment('----------------------------------');
    }
}
