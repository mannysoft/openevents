<?php

namespace App\Livewire\Admin\Orders\Show;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public function render()
    {
        return view('livewire.admin.orders.show');
    }
}
