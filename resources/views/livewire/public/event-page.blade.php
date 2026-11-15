<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
    <div>
        @if ($event->coverImageUrl())
            <img src="{{ $event->coverImageUrl() }}" alt="{{ $event->title }}" class="mb-6 h-64 w-full rounded-xl object-cover">
        @endif

        <p class="text-sm font-medium text-slate-500">
            <a href="{{ route('organisers.show', $event->organiser) }}" class="hover:underline" wire:navigate>{{ $event->organiser->name }}</a>
        </p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight">{{ $event->title }}</h1>

        <dl class="mt-6 grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">When</dt>
                <dd class="mt-1 text-sm">
                    {{ $event->start_date->clone()->setTimezone($event->timezone)->format('l j F Y, H:i') }}
                    @if ($event->end_date)
                        &ndash; {{ $event->end_date->clone()->setTimezone($event->timezone)->format('H:i') }}
                    @endif
                </dd>
            </div>
            @if ($event->venue_name || $event->address1 || $event->city)
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Where</dt>
                    <dd class="mt-1 text-sm">
                        {{ collect([$event->venue_name, $event->address1, $event->city, $event->postal_code])->filter()->join(', ') }}
                    </dd>
                </div>
            @endif
        </dl>

        @if ($event->description)
            <div class="prose prose-slate mt-8 max-w-none text-sm leading-6 text-slate-700">
                {!! nl2br(e($event->description)) !!}
            </div>
        @endif
    </div>

    <aside class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold">Tickets</h2>

        <ul class="mt-4 space-y-3">
            @forelse ($event->tickets as $ticket)
                <li class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3 last:border-0">
                    <div>
                        <p class="text-sm font-medium">{{ $ticket->title }}</p>
                        @if ($ticket->description)
                            <p class="text-xs text-slate-500">{{ $ticket->description }}</p>
                        @endif
                        @if ($ticket->remaining() !== null)
                            <p class="text-xs text-slate-500">{{ $ticket->remaining() }} remaining</p>
                        @endif
                    </div>
                    <p class="text-sm font-semibold">{{ $ticket->priceLabel() }}</p>
                </li>
            @empty
                <li class="text-sm text-slate-500">Tickets are not available yet.</li>
            @endforelse
        </ul>

        <div class="mt-5">
            @if ($event->tickets->contains(fn ($ticket) => $ticket->isOnSale()))
                <a href="{{ route('checkout', $event) }}" class="block w-full rounded-md bg-slate-900 px-4 py-2.5 text-center text-sm font-medium text-white hover:bg-slate-700" wire:navigate>
                    Get tickets
                </a>
            @else
                <p class="rounded-md bg-slate-100 px-4 py-3 text-sm text-slate-600">
                    Tickets are not on sale at the moment.
                </p>
            @endif
        </div>
    </aside>
</div>
