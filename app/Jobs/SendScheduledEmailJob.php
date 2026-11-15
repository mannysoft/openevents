<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SendScheduledEmailJob implements ShouldQueue
{
    use Queueable;

    public $scheduledEmail;

    /**
     * Create a new job instance.
     */
    public function __construct($scheduledEmail)
    {
        $this->scheduledEmail = $scheduledEmail;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // https://app.gohighlevel.com/v2/location/ZDavdc0F1sm0KU0x8ggQ/automation/workflow/192b1349-ff7f-402f-ab3f-fff3b6963fb9
        $schedule = $this->scheduledEmail;
        $response = Http::post('https://services.leadconnectorhq.com/hooks/ZDavdc0F1sm0KU0x8ggQ/webhook-trigger/f27b4557-07e2-4e96-9d5f-2b565b67d075', [
            'first_name' => $schedule->first_name,
            'last_name' => $schedule->last_name,
            'email' => $schedule->email, // $visitor->email_id
            'event_id' => $schedule->event_id,
            'event_name' => 'x',
            'event_url' => config('app.url') . '/club/' . $schedule->event_id,
            'contact_no' => $schedule->mobile_no,
            'days' => $schedule->type
        ]);

        if ($response->ok()) {
            $schedule->delete();
        }
    }
}
