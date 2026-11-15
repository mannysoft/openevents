<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\VisitLog;
use App\Models\EventPurchase;
use App\Models\Signup;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use App\Services\GoHighLevel;
use App\Jobs\SendEmailGhl;

class SendVisitorEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:visitor-email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send visitor email to GHL';

    /**
     * Execute the console command.s
     *
     * @return mixed
     */
    public function handle()
    {
        $events = Event::query()
            // ->whereHas('signups', function (Builder $query) {
            //     $query->where('three_days_checked', false);
            // })
            ->with('visitLogs', 'signups')
            ->where('event_date', now()->addDays(3)->format('Y-m-d'))
            ->limit(1)
            ->get();

        // dd($events);
        // dd(now()->addDays(3)->format('Y-m-d'));

        foreach ($events as $event) {
            // dd($event->id); //2119
            // dd($event->visitLogs->pluck('email_id'));

            // Email address of the visitor
            // $emails = $event->visitLogs->pluck('email_id');

            // dd($event->signups->count());


            // dd($event->signups->pluck('email_id'));

            $emails = $event
                ->signups
                ->where('three_days_checked', false)
                ->pluck('email_id')
                ->unique()
                ->toArray();

            dd($emails);

            // Check if the email already purchased tickets
            foreach ($emails as $email) {
                $purchase = EventPurchase::query()
                    ->where('event_id', $event->id)
                    ->where('email_id', $email)
                    ->first();

                if ($purchase) {
                    $this->comment('Already purchased: ' . $email);
                    Log::info('Already Purchased: ' . $email . ', event_id: ' . $event->id);

                    // Update the signup record
                    // $signup = Signup::query()
                    //     ->where('event_id', $event->id)
                    //     ->where('email_id', $email)
                    //     ->first();
                    continue;
                }

                // Send the email
                // We are going to use Model App\Models\Signup
                // then the field should be three_days_checked and one_day_checked
                // dispatch(new SendEmailGhl($visitor, 2, false));

                // $this->comment('Email not purchased: ' . $email);
                // Log::info('Email not purchased: ' . $email . ', event_id: ' . $event->id);
            }

            dd($emails);
        }

        return;

        Log::info('Send visitor email to GHL, date: ' . now()->format('Y-m-d H:i:s'));
        // TODO
        // Email 1 - First day of visit
        // Email 2 - 2 days after visit
        // Email 3 - 3 days before event date
        // Email 4 - 1 day before the event date

        // https://services.leadconnectorhq.com/hooks/ZDavdc0F1sm0KU0x8ggQ/webhook-trigger/f27b4557-07e2-4e96-9d5f-2b565b67d075

        $visitorsToday = VisitLog::query()
            // ->with('purchase')
            ->firstDay()
            ->limit(1)
            ->get();

        foreach ($visitorsToday as $visitor) {

            $purchase = EventPurchase::query()
                ->where('event_id', $visitor->event_id)
                ->where('email_id', $visitor->email_id)
                ->first();

            if ($purchase) {
                $visitor->first_day_checked = true;
                $visitor->save();

                $this->comment('First, Already purchased: ' . $visitor->email_id);
                Log::info('First, Already purchased: ' . $visitor->email_id . ', event_id: ' . $visitor->event_id);
                continue;
            }

            // Send the email
            dispatch(new SendEmailGhl($visitor, 1, true));

            $this->comment('First, Email not purchased: ' . $visitor->email_id);
            Log::info('First, Email not purchased: ' . $visitor->email_id . ', event_id: ' . $visitor->event_id);
        }

        $this->comment('Done first day!');
        Log::info('Done first day!');

        $this->comment('----------------------------------');

        $visitorsYerterday = VisitLog::query()
            ->secondDay()
            ->limit(1)
            ->get();

        foreach ($visitorsYerterday as $visitor) {

            $purchase = EventPurchase::query()
                ->where('event_id', $visitor->event_id)
                ->where('email_id', $visitor->email_id)
                ->first();

            if ($purchase) {
                $visitor->second_day_checked = true;
                $visitor->save();

                $this->comment('Second, Already purchased: ' . $visitor->email_id);
                Log::info('Second, Already Purchased: ' . $visitor->email_id . ', event_id: ' . $visitor->event_id);
                continue;
            }

            // Send the email
            dispatch(new SendEmailGhl($visitor, 2, false));

            $this->comment('Second, Email not purchased: ' . $visitor->email_id);
            Log::info('Second, Email not purchased: ' . $visitor->email_id . ', event_id: ' . $visitor->event_id);
        }

        $this->comment('Done second day!');




        $this->comment('----------------------------------');

        $events = Event::query()
            ->where('event_date', now()->addDays(3)->format('Y-m-d'))
            ->limit(1)
            ->get();

        foreach ($events as $visitor) {

            $purchase = EventPurchase::query()
                ->where('event_id', $visitor->event_id)
                ->where('email_id', $visitor->email_id)
                ->first();

            if ($purchase) {
                $visitor->second_day_checked = true;
                $visitor->save();

                $this->comment('Second, Already purchased: ' . $visitor->email_id);
                Log::info('Second, Already Purchased: ' . $visitor->email_id . ', event_id: ' . $visitor->event_id);
                continue;
            }

            // Send the email
            dispatch(new SendEmailGhl($visitor, 2, false));

            $this->comment('Second, Email not purchased: ' . $visitor->email_id);
            Log::info('Second, Email not purchased: ' . $visitor->email_id . ', event_id: ' . $visitor->event_id);
        }

        $this->comment('Done second day!');



        $this->comment('----------------------------------');
    }
}
