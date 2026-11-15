<?php

use App\Livewire\Admin\Attendees\Index as AttendeesIndex;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

DB::beginTransaction();

try {
    Auth::login(User::findOrFail(1));
    $event = Event::firstOrFail();
    $order = Order::create([
        'reference' => 'ORD-'.Str::upper(Str::random(8)),
        'event_id' => $event->id,
        'organiser_id' => $event->organiser_id,
        'status' => 'completed',
        'email' => 'b@example.com',
        'currency_code' => $event->currency_code,
    ]);
    $attendee = Attendee::create([
        'order_id' => $order->id,
        'event_id' => $event->id,
        'email' => 'b@example.com',
        'reference' => 'TKT-'.Str::upper(Str::random(10)),
    ]);

    $component = Livewire::test(AttendeesIndex::class, ['event' => $event]);
    $component->call('resendTicket', attendeeId: $attendee->id);

    echo 'session keys: '.implode(',', array_keys(session()->all())).PHP_EOL;
    echo 'session status: '.var_export(session('status'), true).PHP_EOL;
} catch (Throwable $e) {
    echo 'EXCEPTION: '.$e->getMessage().PHP_EOL;
} finally {
    DB::rollBack();
}
