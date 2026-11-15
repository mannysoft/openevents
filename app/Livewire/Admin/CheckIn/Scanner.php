<?php

namespace App\Livewire\Admin\CheckIn;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Scanner extends Component
{
    public function render()
    {
        return view('livewire.admin.checkin.index');
    }
}
