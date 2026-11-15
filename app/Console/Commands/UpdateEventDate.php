<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Event;

class UpdateEventDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:update-date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the date of the event';

    /**
     * Execute the console command.s
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info('UpdateEventDate');

        $events = Event::query()
            ->whereNull('event_date')
            ->limit(100)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($events as $event) {
            $event->event_date = $event->start_date->format('Y-m-d');
            $event->save();
        }
        $this->comment('----------------------------------');
    }
}
