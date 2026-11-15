<?php

namespace App\Livewire\Admin\Organisers;

use App\Models\Organiser;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public function render()
    {
        $organisers = Organiser::query()
            ->where('user_id', auth()->id())
            ->withCount('events')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.organisers.index', [
            'organisers' => $organisers,
        ])->title('Organisers');
    }
}
