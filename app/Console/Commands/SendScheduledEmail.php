<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\EmailSchedule;
use App\Jobs\SendScheduledEmailJob;

class SendScheduledEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:scheduled-email';

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
        $scheduledEmails = EmailSchedule::query()
            ->where('date_send', now()->format('Y-m-d'))
            ->where('type', '!=', 1)
            ->limit(10)
            ->get();

        foreach ($scheduledEmails as $scheduledEmail) {

            dispatch(new SendScheduledEmailJob($scheduledEmail));

            Log::info('SendScheduledEmail sent!');
        }
    }
}
