@component('mail::message')
# You're going to {{ $event->title }}

Hi {{ $order->name() ?: $order->email }},

Your payment of **{{ $order->totalLabel() }}** has been received. Here are your tickets.

@foreach ($attendees as $attendee)
- **{{ $attendee->name() ?: $attendee->email }}** &mdash; {{ $attendee->ticket?->title }} ({{ $attendee->reference }})
@endforeach

**When:** {{ $event->start_date->clone()->setTimezone($event->timezone)->format('l j F Y, H:i') }}
@if ($event->venue_name || $event->city)
**Where:** {{ collect([$event->venue_name, $event->address1, $event->city])->filter()->join(', ') }}
@endif

@component('mail::button', ['url' => route('orders.confirmation', $order)])
View your order
@endcomponent

Thanks,<br>
{{ $event->organiser->name }}
@endcomponent
