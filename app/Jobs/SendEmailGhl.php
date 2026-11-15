<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SendEmailGhl implements ShouldQueue
{
    use Queueable;

    public $visitor;
    public $days;
    public $isFirstDay;

    /**
     * Create a new job instance.
     */
    public function __construct($visitor, $days = 1, $isFirstDay = false)
    {
        $this->visitor = $visitor;
        $this->days = $days;
        $this->isFirstDay = $isFirstDay;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $visitor = $this->visitor;
        $response = Http::post('https://services.leadconnectorhq.com/hooks/ZDavdc0F1sm0KU0x8ggQ/webhook-trigger/f27b4557-07e2-4e96-9d5f-2b565b67d075', [
            'first_name' => $visitor->first_name,
            'last_name' => $visitor->last_name,
            'email' => $visitor->email_id, // $visitor->email_id
            'event_id' => $visitor->event_id,
            'event_name' => 'x',
            'event_url' => config('app.url') . '/club/' . $visitor->event_id,
            'contact_no' => $visitor->mobile_no,
            'days' => $this->days
        ]);

        if ($response->ok()) {
            if ($this->isFirstDay) {
                $visitor->first_day_checked = true;
            } else {
                $visitor->second_day_checked = true;
            }
            $visitor->save();
        }
    }
}
