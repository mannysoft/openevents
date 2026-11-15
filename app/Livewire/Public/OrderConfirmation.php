<?php

namespace App\Livewire\Public\OrderConfirmation;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class OrderConfirmation extends Component
{
    public function render()
    {
        return view('livewire.public.order-confirmation');
    }
}
