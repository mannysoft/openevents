<?php

namespace App\Livewire\Public;

use App\Models\Event;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class EventPage extends Component
{
    public Event $event;

    public function mount(Event $event): void
    {
        abort_unless($event->is_public && $event->isLive(), 404);

        $this->event = $event;
    }

    public function render()
    {
        $this->event->load([
            'organiser',
            'tickets' => fn ($query) => $query->where('is_hidden', false),
        ]);

        return view('livewire.public.event-page', ['event' => $this->event]);
    }
}
