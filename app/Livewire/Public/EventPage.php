<?php

namespace App\Livewire\Public\EventPage;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class EventPage extends Component
{
    public function render()
    {
        return view('livewire.public.event-page');
    }
}
