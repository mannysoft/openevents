<?php

namespace App\Livewire\Public;

use App\Models\Attendee;
use App\Support\Qr;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class Ticket extends Component
{
    public Attendee $attendee;

    public function mount(Attendee $attendee): void
    {
        $attendee->load(['event.organiser', 'ticket', 'order']);

        abort_unless($attendee->order?->isCompleted(), 404);

        $this->attendee = $attendee;
    }

    public function render()
    {
        return view('livewire.public.ticket', [
            'attendee' => $this->attendee,
            'qr' => Qr::svg($this->attendee->reference),
        ]);
    }
}
