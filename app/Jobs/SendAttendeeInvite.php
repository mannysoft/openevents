<?php

namespace App\Jobs;

use App\Mailers\AttendeeMailer;
use App\Models\Attendee;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAttendeeInvite extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, DispatchesJobs;

    public $attendee;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Attendee $attendee)
    {
        $this->attendee = $attendee;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AttendeeMailer $attendeeMailer)
    {
        // dispatchNow() no longer exists in Laravel 12; dispatchSync() is its replacement.
        $this->dispatchSync(new GenerateTicket($this->attendee->reference));
        $attendeeMailer->sendAttendeeInvite($this->attendee);
    }
}
