<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ticket {{ $attendee->reference }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; margin: 0; padding: 32px; }
        .ticket { border: 1px solid #cbd5e1; border-radius: 8px; padding: 28px; }
        .organiser { font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: #64748b; }
        h1 { font-size: 22px; margin: 6px 0 4px; }
        .date { color: #475569; font-size: 13px; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        td { padding: 6px 0; border-bottom: 1px solid #e2e8f0; }
        td.label { color: #64748b; width: 35%; }
        .qr { text-align: center; margin: 24px 0; }
        .reference { font-family: DejaVu Sans Mono, monospace; font-size: 15px; letter-spacing: 1px; }
        .foot { margin-top: 24px; font-size: 11px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="organiser">{{ $event->organiser->name }}</div>
        <h1>{{ $event->title }}</h1>
        <div class="date">
            {{ $event->start_date->clone()->setTimezone($event->timezone)->format('l j F Y, H:i') }}
            @if ($event->venue_name || $event->city)
                &middot; {{ collect([$event->venue_name, $event->city])->filter()->join(', ') }}
            @endif
        </div>

        <div class="qr">
            <img src="{{ \App\Support\Qr::pngDataUri($attendee->reference) }}" alt="Ticket QR code" width="220" height="220">
        </div>

        <table>
            <tr><td class="label">Attendee</td><td>{{ $attendee->name() }}</td></tr>
            <tr><td class="label">Ticket</td><td>{{ $attendee->ticket?->title }}</td></tr>
            <tr><td class="label">Order</td><td>{{ $attendee->order?->reference }}</td></tr>
            <tr><td class="label">Reference</td><td class="reference">{{ $attendee->reference }}</td></tr>
        </table>

        <div class="foot">Present this ticket (printed or on your phone) at the entrance.</div>
    </div>
</body>
</html>
