<?php

namespace App\Livewire\Public;

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class OrderConfirmation extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        $this->order = $order->load(['event.organiser', 'items', 'attendees.ticket']);
    }

    public function refreshStatus(): void
    {
        $this->order->refresh()->load(['event.organiser', 'items', 'attendees.ticket']);
    }

    public function render()
    {
        return view('livewire.public.order-confirmation', ['order' => $this->order]);
    }
}
