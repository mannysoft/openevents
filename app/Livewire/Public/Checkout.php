<?php

namespace App\Livewire\Public\Checkout;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class Checkout extends Component
{
    public function render()
    {
        return view('livewire.public.checkout');
    }
}
