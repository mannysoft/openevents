<?php

namespace App\Livewire\Admin\Attendees;

use App\Mail\TicketIssued;
use App\Models\Attendee;
use App\Models\Event;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Locked]
    public Event $event;

    public string $search = '';

    public string $ticket = '';

    public string $status = '';

    public bool $showEditModal = false;

    public ?int $editingId = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public function mount(Event $event): void
    {
        Gate::authorize('view', $event);

        $this->event = $event;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTicket(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'ticket', 'status');
        $this->resetPage();
    }

    public function edit(int $attendeeId): void
    {
        $attendee = $this->event->attendees()->findOrFail($attendeeId);

        Gate::authorize('update', $attendee);

        $this->editingId = $attendee->id;
        $this->first_name = (string) $attendee->first_name;
        $this->last_name = (string) $attendee->last_name;
        $this->email = (string) $attendee->email;
        $this->showEditModal = true;
    }

    public function save(): void
    {
        $attendee = $this->findAttendee((int) $this->editingId);

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $attendee->forceFill([
            'first_name' => $validated['first_name'],
            'last_name' => filled($validated['last_name']) ? $validated['last_name'] : null,
            'email' => $validated['email'],
        ])->save();

        $this->closeEditModal();

        session()->flash('status', "Updated {$attendee->name()}.");
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingId = null;
        $this->reset('first_name', 'last_name', 'email');
        $this->resetValidation();
    }

    public function cancelAttendee(int $attendeeId, OrderService $orders): void
    {
        $attendee = $this->findAttendee($attendeeId);

        $orders->cancelAttendee($attendee);

        session()->flash('status', "Cancelled {$attendee->name()}'s ticket.");
    }

    public function restoreAttendee(int $attendeeId, OrderService $orders): void
    {
        $attendee = $this->findAttendee($attendeeId);

        $orders->restoreAttendee($attendee);

        session()->flash('status', "Restored {$attendee->name()}'s ticket.");
    }

    public function resendTicket(int $attendeeId): void
    {
        $attendee = $this->findAttendee($attendeeId);

        if (blank($attendee->email)) {
            $this->addError('resend', 'This attendee does not have an email address.');

            return;
        }

        Mail::to($attendee->email)->queue(new TicketIssued($attendee));

        session()->flash('status', "Ticket queued for {$attendee->email}.");
    }

    private function findAttendee(int $attendeeId): Attendee
    {
        $attendee = $this->event->attendees()->findOrFail($attendeeId);

        Gate::authorize('update', $attendee);

        return $attendee;
    }

    /**
     * @return Builder<Attendee>
     */
    private function baseQuery(): Builder
    {
        return $this->event->attendees()
            ->whereHas('order', fn (Builder $query) => $query->where('status', 'completed'));
    }

    /**
     * @return Builder<Attendee>
     */
    private function filteredQuery(): Builder
    {
        return $this->baseQuery()
            ->with(['order', 'ticket'])
            ->when(trim($this->search) !== '', function (Builder $query) {
                $term = '%'.trim($this->search).'%';

                $query->where(function (Builder $query) use ($term) {
                    $query->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('reference', 'like', $term);
                });
            })
            ->when($this->ticket !== '', fn (Builder $query) => $query->where('ticket_id', (int) $this->ticket))
            ->when($this->status !== '', fn (Builder $query) => match ($this->status) {
                'checked-in' => $query->whereNotNull('checked_in_at'),
                'cancelled' => $query->where('is_cancelled', true),
                'refunded' => $query->where('is_refunded', true),
                'active' => $query->where('is_cancelled', false)
                    ->where('is_refunded', false)
                    ->whereNull('checked_in_at'),
                default => $query,
            });
    }

    /**
     * @return array{total: int, checked_in: int, cancelled: int, refunded: int}
     */
    private function summary(): array
    {
        return [
            'total' => $this->baseQuery()->count(),
            'checked_in' => $this->baseQuery()->whereNotNull('checked_in_at')->count(),
            'cancelled' => $this->baseQuery()->where('is_cancelled', true)->count(),
            'refunded' => $this->baseQuery()->where('is_refunded', true)->count(),
        ];
    }

    public function render()
    {
        return view('livewire.admin.attendees.index', [
            'attendees' => $this->filteredQuery()->latest('id')->paginate(20),
            'tickets' => $this->event->tickets()->get(),
            'summary' => $this->summary(),
        ]);
    }
}
