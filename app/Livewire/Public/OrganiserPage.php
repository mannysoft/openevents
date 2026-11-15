<?php

namespace App\Livewire\Public;

use App\Models\Organiser;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.public')]
class OrganiserPage extends Component
{
    use WithPagination;

    public Organiser $organiser;

    public function mount(Organiser $organiser): void
    {
        $this->organiser = $organiser;
    }

    public function render()
    {
        $events = $this->organiser->events()
            ->published()
            ->upcoming()
            ->with('tickets')
            ->orderBy('start_date')
            ->paginate(12);

        return view('livewire.public.organiser-page', [
            'events' => $events,
            'organiser' => $this->organiser,
        ]);
    }
}
