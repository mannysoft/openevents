<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\LoungeEventSection;
use Illuminate\Support\Collection;

class MagazineSection extends Component
{
    public $globalmanclub = false;

    public $eventId;

    public bool $shouldDisplay = true;

    public bool $isEdit = false;

    public ?Collection $items = null;

    public string $displayTitle = '';

    public function mount($globalmanclub = false)
    {
        $this->globalmanclub = $globalmanclub;

        $this->isEdit = (bool)request('edit_event');

        $this->items = LoungeEventSection::query()
            ->where('event_id', $this->eventId)
            ->includePlaceholder((bool)request('edit_event'))
            ->get();

        // If no items
        if ($this->items->isEmpty()) {
            // Create empty placeholder
            // Check first if placeholder exists
            $exists = LoungeEventSection::query()
                ->where('event_id', $this->eventId)
                ->where('is_placeholder', true)
                ->first();

            if (!$exists) {
                LoungeEventSection::create([
                    'section_content' => '',
                    'section_image' => '',
                    'is_placeholder' => true,
                    'event_id' => $this->eventId,
                ]);

                $this->items = LoungeEventSection::query()
                    ->where('event_id', $this->eventId)
                    ->includePlaceholder((bool)request('edit_event'))
                    ->get();

            }
        }
    }

    public function addSection()
    {
        $this->isEdit = true;
        LoungeEventSection::create([
            'section_content' => '',
            'section_image' => '',
            'is_placeholder' => true,
            'event_id' => $this->eventId,
        ]);

        $this->items = LoungeEventSection::query()
            ->where('event_id', $this->eventId)
            ->includePlaceholder(true)->get();
    }

    public function removeSection($id)
    {
        $this->isEdit = true;
        if ($item = LoungeEventSection::find($id)) {
            $item->is_placeholder = true;
            $item->save();
            $this->items = LoungeEventSection::includePlaceholder(true)->get();
        }
    }

    public function render()
    {
        return view('livewire.magazine-section');
    }
}
