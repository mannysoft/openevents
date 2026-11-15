<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\Models\EmailSchedule;
use App\Models\Event;
use App\Jobs\SendScheduledEmailJob;

class ScheduleEmailForAttendees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:attendees-email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled email to GHL';

    /**
     * Execute the console command.s
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info('ScheduleEmailForAttendees command started');

        $events = Event::query()
            ->with('eventPurchases')
            ->where('event_date', now()->format('Y-m-d'))
            ->limit(100)
            ->get();

        foreach ($events as $event) {

            foreach ($event->eventPurchases as $purchase) {
                // Email is for users who purchased the event
                // Create a schedule for the next days
                // Mail 1 - 1 day after the event - type - 21
                // Mail 2 - 2 days after the event - type - 22
                // Mail 3 - 4 days after the event - type - 24
                // Mail 4 - 6 days after the event - type - 26

                $eventDate = Carbon::createFromFormat('Y-m-d', $event->event_date);

                $typesDates[21] = $eventDate->addDays(1)->format('Y-m-d');
                // Add the email if not a member
                if ($purchase->is_member == false) {
                    $typesDates[22] = $eventDate->addDays(1)->format('Y-m-d');
                    $typesDates[24] = $eventDate->addDays(2)->format('Y-m-d');
                    $typesDates[26] = $eventDate->addDays(2)->format('Y-m-d');
                }

                $fullname = explode(' ', $purchase->full_name);

                $firstName = $fullname[0] ?? 'First Name';
                $lastName = $fullname[1] ?? 'Last Name';

                if ($purchase->first_name != null) {
                    $firstName = $purchase->first_name;
                }

                if ($purchase->last_name != null) {
                    $lastName = $purchase->last_name;
                }

                // Send only for non members
                // if ($purchase->is_member == false) {
                foreach ($typesDates as $type => $dateSend) {
                    create_email_schedule(
                        $firstName,
                        $lastName,
                        $purchase->email_id,
                        $purchase->mobile_no,
                        $purchase->event_id,
                        $purchase->club,
                        $type,
                        $dateSend
                    );
                }
                // }
            }
        }

        Log::info('ScheduleEmailForAttendees command done.');
    }
}
