<div class="mx-auto max-w-md">
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-slate-900 px-6 py-5 text-white">
            <p class="text-xs uppercase tracking-wide text-slate-300">{{ $attendee->event->organiser->name }}</p>
            <h1 class="mt-1 text-xl font-semibold">{{ $attendee->event->title }}</h1>
        </div>

        <div class="p-6">
            <p class="text-sm text-slate-500">{{ $attendee->event->start_date->clone()->setTimezone($attendee->event->timezone)->format('l j F Y, H:i') }}</p>
            @if ($attendee->event->venue_name || $attendee->event->city)
                <p class="mt-1 text-sm text-slate-600">
                    {{ collect([$attendee->event->venue_name, $attendee->event->city])->filter()->join(', ') }}
                </p>
            @endif

            <div class="mt-6 flex justify-center">
                <div class="h-52 w-52">{!! $qr !!}</div>
            </div>

            <dl class="mt-6 space-y-2 border-t border-slate-200 pt-5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Attendee</dt>
                    <dd class="font-medium">{{ $attendee->name() }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Ticket</dt>
                    <dd class="font-medium">{{ $attendee->ticket?->title }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Reference</dt>
                    <dd class="font-mono font-medium">{{ $attendee->reference }}</dd>
                </div>
                @if ($attendee->isCheckedIn())
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Checked in</dt>
                        <dd class="font-medium">{{ $attendee->checked_in_at->format('j M Y, H:i') }}</dd>
                    </div>
                @endif
            </dl>

            <div class="mt-6 flex items-center justify-between">
                <a href="{{ route('tickets.pdf', $attendee) }}" class="text-sm font-medium text-slate-900 hover:underline">Download PDF</a>
                <a href="{{ route('events.index') }}" class="text-sm text-slate-600 hover:underline" wire:navigate>All events</a>
            </div>
        </div>
    </div>
</div>
