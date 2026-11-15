<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Mail\EventAgenda;
use App\Models\Event;
use App\Models\Order;
use App\Services\GoHighLevel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class SendEventAgenda extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:agenda';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send event agenda to email.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info('Event agenda email sent successfully.');
        return '';
        $events = Event::query()
            ->with('orders')
            ->has('orders')
            ->whereBetween('start_date', [now()->format('Y-m-d') . ' 09:00:00', now()->addDays(23)->format('Y-m-d') . ' 09:00:00'])
            ->whereNull('is_member')
            ->orderBy('start_date')
            ->limit(40)
            ->get();

        // 2024-10-21 09:00:00 - orig for LA
        foreach ($events as $event) {

            // skip if orange county or zurich and london
            if (skip_agenda($event->club)) {
                return true;
            }

            $orders = $event->orders;

            $startDate = $event->start_date;

            $days = $startDate->diffInDays(now());

            // If only 3 days remaining
            if ($days <= 3) {
                foreach ($orders as $order) {

                    $this->sendEmail($order);

                    $this->comment('sent to: ' .  $order->email);

                    // $this->sendEmailGhl($order);
                }
            }
        }

        $this->comment('Success!');

        // $orders = Order::query()
        //     ->with('event')
        //     ->where('agenda_email_sent', false)
        //     ->limit(100)
        //     ->get();

        // foreach ($orders as $order) {

        //     $order = Order::find(6393);

        //     $this->sendEmailGhl($order);

        //     if (is_event_coming($order->event)) {
        //         // Mail::queue(new EventAgenda($order));
        //     }
        // }
    }

    public function sendEmail($order)
    {
        if ($order->agenda_email_sent == true) {
            return false;
        }
        Mail::queue(new EventAgenda($order));
        $order->agenda_email_sent = true;
        $order->save();
    }

    public function sendEmailGhl($order)
    {
        // 67fcab5919298c5edd3e6326
        if ($order->agenda_email_sent == true) {
            return false;
        }
        // Send via GHL service
        $ghl = new GoHighLevel;

        $locationId = $ghl->locationId;

        $contact = $ghl->createUpdateContact([
            'email' => $order->email,
            // 'email' => 'mannysoft@gmail.com',
            'locationId' => $locationId,
        ]);

        if (isset($contact['contact']['id'])) {
            $contactId = $contact['contact']['id'];

            $response = $ghl->sendEmail($contactId, [
                'type' => 'Email',
                'contactId' => $contactId,
                'html' => (string)view('event-agenda', ['order' => $order]),
                'subject' => $order->event->title . ' - The Agenda',
            ]);

            $order->agenda_email_sent = true;
            $order->save();
        }
    }
}
