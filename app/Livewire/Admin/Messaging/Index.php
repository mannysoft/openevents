<?php

namespace App\Livewire\Admin\Messaging;

use App\Enums\OrderStatus;
use App\Jobs\SendAttendeeMessages;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\MessageLog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Messages')]
class Index extends Component
{
    public Event $event;

    public string $subject = '';

    public string $body = '';

    public string $filter = 'all';

    public ?string $ticketId = null;

    public function mount(Event $event): void
    {
        Gate::authorize('view', $event);

        $this->event = $event;
    }

    public function send(): void
    {
        Gate::authorize('update', $this->event);

        $this->validate([
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'filter' => ['required', 'in:all,ticket,checked_in,not_checked_in'],
            'ticketId' => [
                'exclude_unless:filter,ticket',
                'required',
                'integer',
                Rule::exists('tickets', 'id')->where('event_id', $this->event->id),
            ],
        ]);

        $recipients = $this->recipients();

        if ($recipients === []) {
            $this->addError('filter', 'No attendees match the selected recipients.');

            return;
        }

        SendAttendeeMessages::dispatch(
            $this->event->id,
            $this->subject,
            $this->body,
            $recipients,
        );

        MessageLog::create([
            'event_id' => $this->event->id,
            'user_id' => auth()->id(),
            'subject' => $this->subject,
            'body' => $this->body,
            'recipients_count' => count($recipients),
            'sent_at' => now(),
        ]);

        $this->reset('subject', 'body');

        session()->flash('status', 'Message queued for '.count($recipients).' attendees.');
    }

    public function render()
    {
        $recipients = $this->recipients();

        return view('livewire.admin.messages.index', [
            'recipientsCount' => count($recipients),
            'sampleEmails' => array_slice(array_keys($recipients), 0, 5),
            'tickets' => $this->event->tickets()->get(),
            'messageLogs' => MessageLog::query()
                ->with('user')
                ->where('event_id', $this->event->id)
                ->orderByDesc('sent_at')
                ->orderByDesc('id')
                ->limit(10)
                ->get(),
        ])->title('Messages · '.$this->event->title);
    }

    /**
     * @return array<string, string>
     */
    private function recipients(): array
    {
        return Attendee::query()
            ->where('event_id', $this->event->id)
            ->where('is_cancelled', false)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereHas('order', fn ($query) => $query->where('status', OrderStatus::Completed->value))
            ->when($this->filter === 'ticket', fn ($query) => $query->where('ticket_id', $this->ticketId))
            ->when($this->filter === 'checked_in', fn ($query) => $query->whereNotNull('checked_in_at'))
            ->when($this->filter === 'not_checked_in', fn ($query) => $query->whereNull('checked_in_at'))
            ->get(['first_name', 'last_name', 'email'])
            ->unique(fn (Attendee $attendee) => strtolower($attendee->email))
            ->mapWithKeys(fn (Attendee $attendee) => [
                $attendee->email => trim(($attendee->first_name ?? '').' '.($attendee->last_name ?? '')),
            ])
            ->all();
    }
}
