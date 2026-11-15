<?php

namespace App\Console\Commands;

use App\Models\EventPurchase;
use Illuminate\Console\Command;

class UpdateMemberPurchase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchases:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update purchases in events';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $eventPurchases = EventPurchase::query()
            ->with('event')
            ->where('created', '>=', now()->subYears(2)->toDateTimeString())
            ->orderBy('id', 'desc')
            ->get();

        foreach ($eventPurchases as $purchase) {

            $isMember = false;
            if ($purchase->amount == @$purchase->event->mprice) {
                $isMember = true;
            }

            if ($purchase->amount == @$purchase->event->hmprice) {
                $isMember = true;
            }

            if ($purchase->amount == @$purchase->event->nmprice) {
                $isMember = false;
            }

            if ($purchase->amount == @$purchase->event->hnmprice) {
                $isMember = false;
            }

            $purchase->is_member = $isMember;
            $purchase->save();

            $this->comment($isMember . $purchase->created);

            // event
            //     "mprice" => "35"
            // "nmprice" => "69"
            // "hmprice" => "0"
            // "hnmprice" => "0"
        }
        $this->comment('Success!');
    }
}
