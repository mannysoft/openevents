<?php

use App\Livewire\Admin\Attendees\Index as AttendeesIndex;
use App\Livewire\Admin\CheckIn\Scanner;
use App\Mail\TicketIssued;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

$failures = [];
$check = function (string $label, bool $passed) use (&$failures) {
    echo ($passed ? 'PASS ' : 'FAIL ').$label.PHP_EOL;
    if (! $passed) {
        $failures[] = $label;
    }
};

DB::beginTransaction();

try {
    $user = User::findOrFail(1);
    Auth::login($user);
    $event = Event::firstOrFail();

    $order = Order::create([
        'reference' => 'ORD-'.Str::upper(Str::random(8)),
        'event_id' => $event->id,
        'organiser_id' => $event->organiser_id,
        'status' => 'completed',
        'email' => 'buyer@example.com',
        'currency_code' => $event->currency_code,
        'total' => 0,
    ]);

    $make = fn (array $attributes) => Attendee::create(array_merge([
        'order_id' => $order->id,
        'event_id' => $event->id,
        'email' => 'a@example.com',
        'reference' => 'TKT-'.Str::upper(Str::random(10)),
    ], $attributes));

    $ada = $make(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com', 'reference' => 'TKT-AAA1111111']);
    $grace = $make(['first_name' => 'Grace', 'last_name' => 'Hopper', 'email' => 'grace@example.com', 'reference' => 'TKT-BBB2222222']);
    $alan = $make(['first_name' => 'Alan', 'last_name' => 'Turing', 'email' => 'alan@example.com', 'reference' => 'TKT-CCC3333333', 'is_cancelled' => true]);
    $edsger = $make(['first_name' => 'Edsger', 'last_name' => 'Dijkstra', 'email' => 'edsger@example.com', 'reference' => 'TKT-DDD4444444', 'is_refunded' => true]);

    // Attendees index
    $component = Livewire::test(AttendeesIndex::class, ['event' => $event]);
    $html = $component->html();
    $check('index lists attendees', str_contains($html, 'Ada') && str_contains($html, 'Dijkstra'));
    $check('index shows summary', str_contains($html, 'Total attendees'));
    $check('index shows order link', str_contains($html, $order->reference));

    $check('search filters by name', str_contains($component->set('search', 'Lovelace')->html(), 'Ada') && ! str_contains($component->html(), 'Dijkstra'));
    $check('search by reference', str_contains($component->set('search', 'TKT-BBB2222222')->html(), 'Grace'));
    $check('empty state on no match', str_contains($component->set('search', 'zzzzz')->html(), 'No attendees found'));
    $component->set('search', '');

    $check('status filter cancelled', str_contains($component->set('status', 'cancelled')->html(), 'Alan') && ! str_contains($component->html(), 'Ada'));
    $check('status filter refunded', str_contains($component->set('status', 'refunded')->html(), 'Edsger'));
    $component->set('status', '');

    // Edit
    $component->call('edit', attendeeId: $ada->id)
        ->assertSet('showEditModal', true);
    $component->set('first_name', 'Ada Augusta')->set('email', 'ada.a@example.com')->call('save');
    $ada->refresh();
    $check('edit saves name', $ada->first_name === 'Ada Augusta');
    $check('edit saves email', $ada->email === 'ada.a@example.com');
    $check('edit closes modal', ! $component->get('showEditModal'));

    $component->call('edit', attendeeId: $ada->id)->set('email', 'not-an-email')->call('save');
    $check('edit validates email', $component->errors()->has('email'));
    $component->call('closeEditModal');

    // Cancel / restore
    $component->call('cancelAttendee', attendeeId: $ada->id);
    $check('cancel attendee', $ada->refresh()->is_cancelled === true);
    $component->call('restoreAttendee', attendeeId: $ada->id);
    $check('restore attendee', $ada->refresh()->is_cancelled === false);

    // Resend
    $jobsBefore = DB::table('jobs')->count();
    $component->call('resendTicket', attendeeId: $ada->id);
    $check('resend queues mail', DB::table('jobs')->count() === $jobsBefore + 1);
    $check('resend flashes status', session('status') !== null);

    // Mailable
    $mailable = new TicketIssued($ada->fresh());
    $rendered = $mailable->render();
    $check('mailable subject', $mailable->envelope()->subject === 'Your ticket for '.$event->title);
    $check('mailable shows reference', str_contains($rendered, $ada->reference));
    $check('mailable links ticket page', str_contains($rendered, route('tickets.show', $ada)));
    $check('mailable links pdf', str_contains($rendered, route('tickets.pdf', $ada)));

    // Scanner
    $scanner = Livewire::test(Scanner::class, ['event' => $event]);
    $check('scanner shows counters', str_contains($scanner->html(), 'Remaining'));

    $scanner->set('reference', ' tkt-bbb2222222 ')->call('checkIn');
    $check('check-in normalises lowercase', $grace->refresh()->checked_in_at !== null);
    $check('check-in success message', str_contains((string) $scanner->get('successMessage'), 'Grace Hopper'));
    $check('check-in clears input', $scanner->get('reference') === '');
    $check('check-in shows in recent list', str_contains($scanner->html(), 'Grace Hopper'));

    $scanner->set('reference', 'TKT-BBB2222222')->call('checkIn');
    $check('already checked in error', str_contains((string) $scanner->get('errorMessage'), 'already checked in'));

    $scanner->set('reference', '')->call('checkIn');
    $check('required reference validation', $scanner->errors()->has('reference'));

    $scanner->set('reference', 'TKT-CCC3333333')->call('checkIn');
    $check('cancelled attendee error', str_contains((string) $scanner->get('errorMessage'), 'cancelled'));

    $scanner->set('reference', 'TKT-DDD4444444')->call('checkIn');
    $check('refunded attendee error', str_contains((string) $scanner->get('errorMessage'), 'refunded'));

    $scanner->set('reference', 'TKT-NOPE000000')->call('checkIn');
    $check('unknown reference error', str_contains((string) $scanner->get('errorMessage'), 'No ticket found'));

    $scanner->dispatch('barcode-scanned', reference: 'TKT-AAA1111111');
    $check('barcode scanned checks in', $ada->refresh()->checked_in_at !== null);
    $check('barcode scanned success', str_contains((string) $scanner->get('successMessage'), 'Ada Augusta'));

    $check('counters reflect check-ins', str_contains($scanner->html(), 'Remaining'));
} catch (Throwable $e) {
    echo 'EXCEPTION: '.get_class($e).': '.$e->getMessage().PHP_EOL;
    echo $e->getFile().':'.$e->getLine().PHP_EOL;
    $failures[] = 'exception';
} finally {
    DB::rollBack();
}

echo PHP_EOL.'FAILURES: '.count($failures).PHP_EOL;
