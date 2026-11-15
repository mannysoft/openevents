<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;
use Illuminate\Support\Str;

class CreateEventSlug extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:slug';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Event Slug';

    /**
     * Execute the console command.s
     *
     * @return mixed
     */
    public function handle()
    {
        $events = Event::query()->whereNotNull('club')->get();

        foreach ($events as $event) {

            // $event->slug = Str::slug($event->title  . ' ' . $event->club . ' ' . $event->start_date->format('Y-m-d') . ' ' . $event->id);
            $club = $event->club;
            if ($club == null) {
                $club = $event->id;
            }
            $event->slug = Str::slug($club . ' ' . $event->start_date->format('Y-m-d'));
            $event->save();
        }

        $this->comment('Success!');
    }
}
