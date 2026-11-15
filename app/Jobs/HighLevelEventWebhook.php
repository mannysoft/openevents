<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class HighLevelEventWebhook implements ShouldQueue
{
    use Queueable;

    public $event;

    public $email;

    public $club;

    public $eventDate;

    public $eventName;

    public $accessType;

    /**
     * Create a new job instance.
     *
     * @param mixed $event The event object containing event details
     * @param string $email The email of the user
     * @param mixed $club The club associated with the event
     * @param mixed $eventDate The date of the event
     * @param mixed $eventName The name of the event
     * @param mixed $accessType The access type (e.g., visited, guest, booked_ticket)
     */
    public function __construct($event, $email, $club, $eventDate, $eventName, $accessType)
    {
        $this->event = $event;
        $this->email = $email;
        $this->club = $club;
        $this->eventDate = $eventDate;
        $this->eventName = $eventName;
        $this->accessType = $accessType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        // dd($this->event->club);
        $clubs = array(
            array('slug' => 'brighton'),
            array('slug' => 'birmingham'),
            array('slug' => 'london'),
            array('slug' => 'manchester'),
            array('slug' => 'nottingham'),
            array('slug' => 'bournemouth'),
            array('slug' => 'nottinghill'),
            array('slug' => 'windsor'),
            array('slug' => 'leeds'),
            array('slug' => 'london-mayfair'),
            array('slug' => 'liverpool'),
            array('slug' => 'cheshire'),
            array('slug' => 'cobham'),
            array('slug' => 'malta'),
            array('slug' => 'norwich'),
            array('slug' => 'essex'),
            array('slug' => 'streatham'),
            array('slug' => 'surrey'),
            array('slug' => 'bristol'),
            array('slug' => 'northampton'),
            array('slug' => 'southampton'),
            array('slug' => 'kent'),
            array('slug' => 'richmond'),
            array('slug' => 'wimbledon'),
            array('slug' => 'bath'),
            array('slug' => 'cardiff'),
            array('slug' => 'kensington'),
            array('slug' => 'oxford'),
            array('slug' => 'kingston'),
            array('slug' => 'sussex'),
            array('slug' => 'exeter'),
            array('slug' => 'plymouth'),
            array('slug' => 'truro'),
            array('slug' => 'chester'),
            array('slug' => 'derby'),
            array('slug' => 'miltonkeynes'),
            );

        // if the club is not from uk then just exit
        if (!collect($clubs)->pluck('slug')->contains($this->event->club)) {
            return;
        }

        // https://app.gohighlevel.com/v2/location/ZDavdc0F1sm0KU0x8ggQ/automation/workflow/cf74d6d5-9ac8-46a6-bac5-b044024f087c
        $response = Http::post('https://services.leadconnectorhq.com/hooks/ZDavdc0F1sm0KU0x8ggQ/webhook-trigger/90ad99b1-8fd2-4c81-87d3-61a4d0294643', [
            'email' => $this->email,
            'club' => $this->club,
            'event_date' => $this->eventDate,
            'event_name' => $this->eventName,
            'access_type' => $this->accessType,  // visited, guest, booked_ticket
        ]);

        if ($response->ok()) {
            // Webhook triggered successfully
            // You can add any additional logic here if needed
        }
    }
}
