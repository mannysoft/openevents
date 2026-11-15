<?php

namespace App\Livewire\Admin\Events;

use App\Models\Organiser;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public Organiser $organiser;

    public function mount(Organiser $organiser): void
    {
        Gate::authorize('view', $organiser);

        $this->organiser = $organiser;
    }

    public function render()
    {
        $events = $this->organiser->events()
            ->orderByDesc('start_date')
            ->paginate(12);

        return view('livewire.admin.events.index', [
            'events' => $events,
        ])->title($this->organiser->name);
    }
}
