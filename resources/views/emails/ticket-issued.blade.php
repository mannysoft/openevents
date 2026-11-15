@php
    $attendeeName = $attendee->name() ?: 'there';
    $venue = collect([$event->venue_name, $event->address1, $event->city, $event->postal_code])
        ->filter()
        ->implode(', ');
@endphp

<x-mail::message>
# Your ticket for {{ $event->title }}

Hi {{ $attendeeName }},

Here is your ticket again. Please have it ready when you arrive.

- **Attendee:** {{ $attendeeName }}
- **Event:** {{ $event->title }}
- **Date:** {{ $event->start_date->format('D j M Y, H:i') }} ({{ $event->timezone }})
@if ($venue)
- **Venue:** {{ $venue }}
@endif
@if ($ticket)
- **Ticket:** {{ $ticket->title }}
@endif
- **Reference:** {{ $attendee->reference }}

<x-mail::button :url="route('tickets.show', $attendee)">
View your ticket
</x-mail::button>

You can also [download the PDF]({{ route('tickets.pdf', $attendee) }}).

If you have any questions, just reply to this email.

Thanks,<br>
{{ $event->organiser?->name ?? config('app.name') }}
</x-mail::message>
