<?php

namespace App\Livewire\Admin\Organisers;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.admin.organisers.index');
    }
}
