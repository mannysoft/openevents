<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\EmailSchedule;
use App\Jobs\SendScheduledEmailJob;

class SendEmailNow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:email-now';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled email to GHL now.';

    /**
     * Execute the console command.s
     *
     * @return mixed
     */
    public function handle()
    {
        $scheduledEmails = EmailSchedule::query()
            ->where('type', 1)
            ->limit(30)
            ->get();

        foreach ($scheduledEmails as $scheduledEmail) {

            dispatch(new SendScheduledEmailJob($scheduledEmail));
        }

        // Log::info('SendEmailNow sent!');
    }
}
