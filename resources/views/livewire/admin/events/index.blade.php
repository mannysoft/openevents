<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-sm text-slate-500">
                <a href="{{ route('admin.organisers.index') }}" wire:navigate class="hover:text-slate-900">Organisers</a>
                <span class="mx-1">/</span>
                <span>{{ $organiser->name }}</span>
            </p>
            <h1 class="mt-1 text-xl font-semibold">{{ $organiser->name }} events</h1>

            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                @if ($organiser->stripeReady())
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-800">Stripe connected</span>
                @else
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-800">Stripe not connected</span>
                @endif
                <span class="rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-700">{{ $organiser->currency_code }}</span>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-700">{{ $organiser->timezone }}</span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.organisers.stripe', $organiser) }}" wire:navigate
               class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                Stripe settings
            </a>
            <a href="{{ route('admin.events.create', $organiser) }}" wire:navigate
               class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                Create event
            </a>
        </div>
    </div>

    @if ($events->isEmpty())
        <x-ui.card>
            <p class="text-sm text-slate-600">No events yet. Create your first event to start selling tickets.</p>
        </x-ui.card>
    @else
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Event</th>
                        <th class="px-4 py-3">Dates</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Tickets sold</th>
                        <th class="px-4 py-3 text-right">Revenue</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($events as $event)
                        @php
                            $start = $event->start_date?->copy()->setTimezone($event->timezone);
                            $end = $event->end_date?->copy()->setTimezone($event->timezone);
                        @endphp
                        <tr wire:key="event-{{ $event->id }}" class="align-top">
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-900">{{ $event->title }}</p>
                                <p class="text-xs text-slate-500">{{ $event->venue_name ?: 'No venue set' }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $start?->format('D j M Y, H:i') }}
                                @if ($end)
                                    <span class="text-slate-400">→</span>
                                    {{ $end->isSameDay($start) ? $end->format('H:i') : $end->format('D j M Y, H:i') }}
                                @endif
                                <span class="block text-xs text-slate-400">{{ $event->timezone }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusClasses = match ($event->status) {
                                        \App\Enums\EventStatus::Live => 'bg-emerald-100 text-emerald-800',
                                        \App\Enums\EventStatus::Ended => 'bg-slate-200 text-slate-700',
                                        default => 'bg-amber-100 text-amber-800',
                                    };
                                @endphp
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClasses }}">
                                    {{ $event->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-700">{{ $event->ticketsSold() }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-700">
                                {{ \App\Support\Money::format($event->revenueMinor(), $event->currency_code) }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-3 text-sm">
                                    <a href="{{ route('admin.events.edit', [$organiser, $event]) }}" wire:navigate class="font-medium text-slate-900 hover:underline">Edit</a>
                                    <a href="{{ route('admin.tickets.index', $event) }}" wire:navigate class="text-slate-600 hover:text-slate-900 hover:underline">Tickets</a>
                                    <a href="{{ route('admin.orders.index', $event) }}" wire:navigate class="text-slate-600 hover:text-slate-900 hover:underline">Orders</a>
                                    @if ($event->isLive() && $event->is_public)
                                        <a href="{{ route('events.show', $event) }}" target="_blank" class="text-slate-600 hover:text-slate-900 hover:underline">View</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $events->links() }}
        </div>
    @endif
</div>
