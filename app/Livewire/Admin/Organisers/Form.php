<?php

namespace App\Livewire\Admin\Organisers\Form;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    public function render()
    {
        return view('livewire.admin.organisers.form');
    }
}
