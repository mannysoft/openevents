<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SpeakingSlot as DomainSpeakingSlot;

class SpeakingSlot extends Component
{
    public $event;

    public $purchased = false;

    public $speakingSlots;

    public $addedToCart = false;

    public function mount()
    {
        $this->speakingSlots = $this->event->speakingSlots()->get();
        $this->addedToCart = session()->has('speaking_slot');
    }

    public function addCart($id)
    {
        session(['speaking_slot' => $id]);

        $this->addedToCart = true;

        $slot = DomainSpeakingSlot::find($id);
        session(['speaking_slot_details' => $slot]);
    }

    public function removeCart($id)
    {
        session(['speaking_slot' => null, 'speaking_slot_details' => null]);

        $this->addedToCart = false;
    }

    public function render()
    {
        return view('livewire.speaking-slot');

    }
}
