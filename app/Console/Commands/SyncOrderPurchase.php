<?php

namespace App\Console\Commands;

use App\Models\EventPurchase;
use App\Models\LobbyUser;
use App\Models\Order;
use Illuminate\Console\Command;

class SyncOrderPurchase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync orders to purchases';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $orders = Order::query()
            ->where('created_at', '>=', now()->subDays(30)->toDateTimeString())
            ->orderBy('id', 'desc')
            ->get();

        foreach ($orders as $order) {

            $eventPurchase = EventPurchase::firstOrNew([
                'event_id' => $order->event_id,
                'email_id' => $order->email
            ]);

            // If exists then skip
            if ($eventPurchase->id) {
                $this->comment('continue!');
                continue;
            }

            $club = $order->event?->club;
            $user = LobbyUser::query()->where('email', $order->email)->first();

            $eventPurchase->full_name = $order->first_name . ' ' . $order->last_name;
            $eventPurchase->first_name = $order->first_name;
            $eventPurchase->last_name = $order->last_name;
            $eventPurchase->mobile_no = $order->contact_no;
            $eventPurchase->club = $club;
            $eventPurchase->amount = $order->total_amount;
            $eventPurchase->user_id = $user?->user_id;
            $eventPurchase->created = $order->created_at;
            $eventPurchase->is_member = $order->is_member;
            $eventPurchase->is_purchased = (bool) $order->total_amount;
            $eventPurchase->save();
            $this->comment('Save!');

        }
        $this->comment('Success!');
    }
}
