<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\GoHighLevel;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facadevebes\Artisan;

class EventVisitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:visitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push event visitor to GHL';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $events = Event::where('start_date', '>=', Carbon::now()->toDateTimeString())->get();

        foreach ($events as $event) {
            
            $visits = DB::table('Wo_Clickfunnel_Event_visit_logs')
                ->whereNotIn('email_id',
                    DB::table('Wo_Clickfunnel_Event_Purchase')
                        ->select('email_id')
                        ->where('event_id', $event->id)
                        ->get()
                        ->toArray()
                )
                ->where('event_id', $event->id)
                ->get();

            foreach ($visits as $visit) {

                $this->comment('Event ID:' . $event->id);

                $this->comment('Event Start date:' . $event->start_date);

                $this->comment('Visit ID:' . $visit->id);

                $this->comment('Email ID:' . $visit->email_id);

                dd('ok');

                $eventDateTag = $visit->club . '-' .  str_replace('-', '', substr($event->start_date, 0, 10));
                $ghl = new GoHighLevel;
                $locationId = $ghl->locationId;
                $contact = $ghl->createUpdateContact([
                    'firstName' => $visit->first_name,
                    'lastName' => $visit->last_name,
                    'email' => $visit->email_id,
                    'locationId' => $locationId,
                ]);

                if (isset($contact['contact']['id'])) {
                    $contact = $ghl->addTags($contact['contact']['id'], ['eventvisits', $eventDateTag]);
                }

            }
        }

        $this->comment('Success!');
    }
}
