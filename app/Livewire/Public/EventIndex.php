<?php

namespace App\Livewire\Public\EventIndex;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class EventIndex extends Component
{
    public function render()
    {
        return view('livewire.public.event-index');
    }
}
