<?php

namespace App\Livewire\Public;

use App\Models\Event;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.public')]
class EventIndex extends Component
{
    use WithPagination;

    public function render()
    {
        $events = Event::query()
            ->published()
            ->upcoming()
            ->with(['organiser', 'tickets'])
            ->orderBy('start_date')
            ->paginate(12);

        return view('livewire.public.event-index', ['events' => $events]);
    }
}
