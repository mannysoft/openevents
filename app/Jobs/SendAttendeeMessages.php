<?php

namespace App\Jobs;

use App\Mail\AttendeeMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendAttendeeMessages implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string>  $recipients
     */
    public function __construct(
        public int $eventId,
        public string $subject,
        public string $body,
        public array $recipients = [],
    ) {}

    public function handle(): void
    {
        foreach (array_chunk($this->recipients, 50, true) as $chunk) {
            foreach ($chunk as $email => $name) {
                Mail::to($email, $name !== '' ? $name : null)
                    ->send(new AttendeeMessage($this->subject, $this->body));
            }
        }
    }
}
