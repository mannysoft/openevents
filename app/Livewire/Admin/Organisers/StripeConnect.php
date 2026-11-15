<?php

namespace App\Livewire\Admin\Organisers\StripeConnect;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class StripeConnect extends Component
{
    public function render()
    {
        return view('livewire.admin.organisers.stripe-connect');
    }
}
