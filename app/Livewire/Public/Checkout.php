<?php

namespace App\Livewire\Public;

use App\Models\Event;
use App\Models\Ticket;
use App\Services\OrderService;
use App\Services\Stripe\CheckoutService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Stripe\Exception\ApiErrorException;

#[Layout('components.layouts.public')]
class Checkout extends Component
{
    #[Locked]
    public Event $event;

    public int $step = 1;

    /** @var array<int, int> ticket id => quantity */
    public array $quantities = [];

    /** @var array<int, array<string, mixed>> */
    public array $attendees = [];

    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $discountCode = '';

    public ?string $orderReference = null;

    public ?string $clientSecret = null;

    public int $total = 0;

    public function mount(Event $event): void
    {
        abort_unless($event->is_public && $event->isLive(), 404);

        $this->event = $event->load(['tickets', 'questions', 'organiser']);
    }

    public function increment(int $ticketId): void
    {
        $ticket = $this->tickets()->firstWhere('id', $ticketId);

        if (! $ticket instanceof Ticket) {
            return;
        }

        $current = (int) ($this->quantities[$ticketId] ?? 0);
        $limit = min($ticket->max_per_order, $ticket->remaining() ?? $ticket->max_per_order);

        $this->quantities[$ticketId] = min($current + 1, $limit);
    }

    public function decrement(int $ticketId): void
    {
        $current = (int) ($this->quantities[$ticketId] ?? 0);
        $this->quantities[$ticketId] = max(0, $current - 1);
    }

    public function continueToDetails(): void
    {
        if (! $this->event->organiser->stripeReady()) {
            $this->addError('quantities', 'This organiser is not accepting payments yet.');

            return;
        }

        $lines = $this->selectedLines();

        $this->buildAttendeeSlots($lines);
        $this->step = 2;
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function pay(OrderService $orders, CheckoutService $checkout): void
    {
        $rules = [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'attendees.*.first_name' => ['required', 'string', 'max:255'],
            'attendees.*.last_name' => ['required', 'string', 'max:255'],
            'attendees.*.email' => ['required', 'email', 'max:255'],
            'discountCode' => ['nullable', 'string', 'max:50'],
        ];

        foreach ($this->event->questions as $question) {
            if ($question->is_required) {
                $rules["attendees.*.answers.{$question->id}"] = ['required'];
            }
        }

        $this->validate($rules);

        $lines = $this->selectedLines();

        $attendees = array_map(fn (array $attendee) => [
            'ticket_id' => (int) $attendee['ticket_id'],
            'first_name' => $attendee['first_name'] ?? null,
            'last_name' => $attendee['last_name'] ?? null,
            'email' => $attendee['email'] ?? null,
            'answers' => $attendee['answers'] ?? [],
        ], $this->attendees);

        $order = $orders->createReservation(
            $this->event,
            [
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'email' => $this->email,
            ],
            $lines,
            $attendees,
            $this->discountCode !== '' ? $this->discountCode : null,
        );

        if ($order->total === 0) {
            $orders->complete($order);

            $this->redirect(route('orders.confirmation', $order->reference), navigate: true);

            return;
        }

        try {
            $intent = $checkout->createPaymentIntent($order);
        } catch (ApiErrorException $exception) {
            $orders->cancel($order);

            report($exception);

            $this->addError('payment', 'We could not start the payment. Please try again.');

            return;
        }

        $this->orderReference = $order->reference;
        $this->clientSecret = $intent->client_secret;
        $this->total = $order->total;
        $this->step = 3;
    }

    public function subtotalMinor(): int
    {
        return $this->tickets()->reduce(function (int $carry, Ticket $ticket) {
            $quantity = (int) ($this->quantities[$ticket->id] ?? 0);

            return $carry + ($ticket->price * $quantity);
        }, 0);
    }

    public function render()
    {
        return view('livewire.public.checkout', [
            'tickets' => $this->tickets(),
            'currency' => $this->event->currency_code,
            'subtotal' => $this->subtotalMinor(),
        ]);
    }

    /**
     * @return Collection<int, Ticket>
     */
    private function tickets()
    {
        return $this->event->tickets
            ->where('is_hidden', false)
            ->filter(fn (Ticket $ticket) => $ticket->isOnSale())
            ->values();
    }

    /**
     * @return array<int, int>
     */
    private function selectedLines(): array
    {
        $lines = [];

        foreach ($this->quantities as $ticketId => $quantity) {
            $quantity = (int) $quantity;

            if ($quantity < 1) {
                continue;
            }

            $ticket = $this->tickets()->firstWhere('id', (int) $ticketId);

            if (! $ticket instanceof Ticket) {
                throw ValidationException::withMessages(['quantities' => 'A selected ticket is no longer available.']);
            }

            $remaining = $ticket->remaining();

            if ($remaining !== null && $quantity > $remaining) {
                throw ValidationException::withMessages([
                    'quantities' => "Only {$remaining} of \"{$ticket->title}\" remaining.",
                ]);
            }

            $lines[$ticket->id] = $quantity;
        }

        if ($lines === []) {
            throw ValidationException::withMessages(['quantities' => 'Choose at least one ticket.']);
        }

        return $lines;
    }

    /**
     * @param  array<int, int>  $lines
     */
    private function buildAttendeeSlots(array $lines): void
    {
        $previous = $this->attendees;
        $slots = [];

        foreach ($lines as $ticketId => $quantity) {
            for ($i = 0; $i < $quantity; $i++) {
                $slots[] = [
                    'ticket_id' => $ticketId,
                    'first_name' => '',
                    'last_name' => '',
                    'email' => '',
                    'answers' => [],
                ];
            }
        }

        foreach ($slots as $index => $slot) {
            $old = $previous[$index] ?? null;

            if (is_array($old) && (int) $old['ticket_id'] === $slot['ticket_id']) {
                $slots[$index] = array_merge($slot, array_intersect_key($old, $slot));
            }
        }

        if (isset($slots[0]) && $slots[0]['email'] === '' && $this->email !== '') {
            $slots[0]['first_name'] = $this->firstName;
            $slots[0]['last_name'] = $this->lastName;
            $slots[0]['email'] = $this->email;
        }

        $this->attendees = $slots;
    }
}
