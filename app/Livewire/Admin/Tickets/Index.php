<?php

namespace App\Livewire\Admin\Tickets;

use App\Models\Event;
use App\Models\Ticket;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public Event $event;

    public bool $showForm = false;

    public ?int $editingId = null;

    public $title = '';

    public $description = '';

    public $price = '0.00';

    public $quantity_available = '';

    public $min_per_order = 1;

    public $max_per_order = 10;

    public $sales_start_at = '';

    public $sales_end_at = '';

    public $is_hidden = false;

    public function mount(Event $event): void
    {
        Gate::authorize('view', $event);

        $this->event = $event;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $sold = $this->editingTicket()?->quantity_sold ?? 0;

        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity_available' => ['nullable', 'integer', 'min:0', 'gte:'.$sold],
            'min_per_order' => ['required', 'integer', 'min:1'],
            'max_per_order' => ['required', 'integer', 'gte:min_per_order'],
            'sales_start_at' => ['nullable', 'date'],
            'sales_end_at' => ['nullable', 'date', 'after_or_equal:sales_start_at'],
            'is_hidden' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'quantity_available.gte' => 'The quantity available cannot be less than the number already sold.',
            'max_per_order.gte' => 'The maximum per order must be at least the minimum per order.',
        ];
    }

    public function create(): void
    {
        Gate::authorize('update', $this->event);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $ticketId): void
    {
        Gate::authorize('update', $this->event);

        $ticket = $this->event->tickets()->findOrFail($ticketId);

        $this->resetErrorBag();
        $this->editingId = $ticket->id;
        $this->title = $ticket->title;
        $this->description = (string) $ticket->description;
        $this->price = (string) Money::toMajor($ticket->price, $this->currency());
        $this->quantity_available = $ticket->quantity_available === null ? '' : (string) $ticket->quantity_available;
        $this->min_per_order = $ticket->min_per_order;
        $this->max_per_order = $ticket->max_per_order;
        $this->sales_start_at = $ticket->sales_start_at?->format('Y-m-d\TH:i') ?? '';
        $this->sales_end_at = $ticket->sales_end_at?->format('Y-m-d\TH:i') ?? '';
        $this->is_hidden = $ticket->is_hidden;
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(): void
    {
        Gate::authorize('update', $this->event);

        $this->normaliseFormInput();

        $this->validate();

        $attributes = [
            'title' => $this->title,
            'description' => $this->description === '' ? null : $this->description,
            'price' => Money::toMinor($this->price, $this->currency()),
            'quantity_available' => $this->quantity_available === null ? null : (int) $this->quantity_available,
            'min_per_order' => (int) $this->min_per_order,
            'max_per_order' => (int) $this->max_per_order,
            'sales_start_at' => $this->sales_start_at === '' ? null : Carbon::parse($this->sales_start_at),
            'sales_end_at' => $this->sales_end_at === '' ? null : Carbon::parse($this->sales_end_at),
            'is_hidden' => (bool) $this->is_hidden,
        ];

        if ($this->editingId !== null) {
            $this->event->tickets()->findOrFail($this->editingId)->update($attributes);
            $message = 'Ticket updated.';
        } else {
            $attributes['sort_order'] = ((int) Ticket::query()->where('event_id', $this->event->id)->max('sort_order')) + 1;

            $this->event->tickets()->create($attributes);
            $message = 'Ticket created.';
        }

        $this->showForm = false;
        $this->resetForm();

        session()->flash('status', $message);
    }

    public function delete(int $ticketId): void
    {
        Gate::authorize('update', $this->event);

        $this->event->tickets()->findOrFail($ticketId)->delete();

        if ($this->editingId === $ticketId) {
            $this->showForm = false;
            $this->resetForm();
        }

        session()->flash('status', 'Ticket deleted.');
    }

    public function toggleHidden(int $ticketId): void
    {
        Gate::authorize('update', $this->event);

        $ticket = $this->event->tickets()->findOrFail($ticketId);
        $ticket->update(['is_hidden' => ! $ticket->is_hidden]);

        session()->flash('status', $ticket->is_hidden ? 'Ticket hidden.' : 'Ticket visible.');
    }

    public function move(int $ticketId, string $direction): void
    {
        Gate::authorize('update', $this->event);

        $ordered = $this->event->tickets()->orderBy('sort_order')->orderBy('id')->get()->all();
        $index = null;

        foreach ($ordered as $position => $ticket) {
            if ($ticket->id === $ticketId) {
                $index = $position;
                break;
            }
        }

        if ($index === null) {
            return;
        }

        $swap = $direction === 'up' ? $index - 1 : $index + 1;

        if (! array_key_exists($swap, $ordered)) {
            return;
        }

        [$ordered[$index], $ordered[$swap]] = [$ordered[$swap], $ordered[$index]];

        foreach ($ordered as $position => $ticket) {
            if ($ticket->sort_order !== $position) {
                $ticket->update(['sort_order' => $position]);
            }
        }
    }

    private function editingTicket(): ?Ticket
    {
        if ($this->editingId === null) {
            return null;
        }

        return $this->event->tickets()->find($this->editingId);
    }

    private function currency(): string
    {
        return $this->event->currency_code;
    }

    private function normaliseFormInput(): void
    {
        $this->quantity_available = ($this->quantity_available === '' || $this->quantity_available === null)
            ? null
            : (int) $this->quantity_available;

        $this->sales_start_at = (string) ($this->sales_start_at ?? '');
        $this->sales_end_at = (string) ($this->sales_end_at ?? '');
        $this->description = (string) ($this->description ?? '');
    }

    private function resetForm(): void
    {
        $this->resetErrorBag();
        $this->editingId = null;
        $this->title = '';
        $this->description = '';
        $this->price = '0.00';
        $this->quantity_available = '';
        $this->min_per_order = 1;
        $this->max_per_order = 10;
        $this->sales_start_at = '';
        $this->sales_end_at = '';
        $this->is_hidden = false;
    }

    public function render()
    {
        return view('livewire.admin.tickets.index', [
            'tickets' => $this->event->tickets()->orderBy('sort_order')->orderBy('id')->get(),
            'currency' => $this->currency(),
        ])->title($this->event->title.' — Tickets');
    }
}
