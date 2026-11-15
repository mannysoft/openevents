<?php

namespace App\Livewire\Admin\CheckIn;

use App\Models\Attendee;
use App\Models\Event;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Scanner extends Component
{
    #[Locked]
    public Event $event;

    public string $reference = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(Event $event): void
    {
        Gate::authorize('view', $event);

        $this->event = $event;
    }

    public function checkIn(OrderService $orders): void
    {
        $this->validate([
            'reference' => ['required', 'string', 'max:64'],
        ]);

        $this->lookupAndCheckIn($this->reference, $orders);
    }

    #[On('barcode-scanned')]
    public function handleScan(string $reference = '', ?OrderService $orders = null): void
    {
        $this->resetValidation('reference');

        $this->lookupAndCheckIn($reference, $orders ?? app(OrderService::class));
    }

    private function lookupAndCheckIn(string $reference, OrderService $orders): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        $code = $this->normaliseReference($reference);

        if ($code === '') {
            $this->errorMessage = 'Enter or scan a ticket reference.';

            return;
        }

        $this->reference = $code;

        $attendee = $this->event->attendees()
            ->with(['order', 'ticket'])
            ->where('reference', $code)
            ->first();

        if (! $attendee instanceof Attendee) {
            $this->errorMessage = "No ticket found for \"{$code}\" at this event.";

            return;
        }

        if ($attendee->is_refunded) {
            $this->errorMessage = "{$attendee->name()} – this ticket has been refunded.";

            return;
        }

        if ($attendee->is_cancelled) {
            $this->errorMessage = "{$attendee->name()} – this ticket has been cancelled.";

            return;
        }

        if (! $attendee->order?->isCompleted()) {
            $this->errorMessage = "{$attendee->name()} – the order for this ticket is not completed.";

            return;
        }

        if ($attendee->isCheckedIn()) {
            $this->errorMessage = "{$attendee->name()} was already checked in at {$attendee->checked_in_at?->format('H:i')}.";

            return;
        }

        if (! $orders->checkIn($attendee, auth()->user())) {
            $this->errorMessage = "Could not check in {$attendee->name()}.";

            return;
        }

        $this->reference = '';
        $this->successMessage = $attendee->ticket?->title
            ? "{$attendee->name()} – {$attendee->ticket->title}"
            : "{$attendee->name()} checked in.";

        $this->dispatch('checkin', name: $attendee->name());
    }

    private function normaliseReference(string $reference): string
    {
        $reference = trim($reference);

        if (str_contains($reference, '/')) {
            $reference = trim(Str::afterLast(rtrim($reference, '/'), '/'));
        }

        return Str::upper(preg_replace('/\s+/', '', $reference) ?? $reference);
    }

    /**
     * @return HasMany<Attendee, $this>
     */
    private function validAttendees(): HasMany
    {
        return $this->event->attendees()
            ->whereHas('order', fn (Builder $query) => $query->where('status', 'completed'))
            ->where('is_cancelled', false)
            ->where('is_refunded', false);
    }

    /**
     * @return array{total: int, checked_in: int, remaining: int}
     */
    private function counters(): array
    {
        $total = $this->validAttendees()->count();
        $checkedIn = $this->validAttendees()->whereNotNull('checked_in_at')->count();

        return [
            'total' => $total,
            'checked_in' => $checkedIn,
            'remaining' => max(0, $total - $checkedIn),
        ];
    }

    public function render()
    {
        return view('livewire.admin.checkin.index', [
            'counters' => $this->counters(),
            'recent' => $this->event->attendees()
                ->with('ticket')
                ->whereNotNull('checked_in_at')
                ->latest('checked_in_at')
                ->limit(10)
                ->get(),
        ]);
    }
}
