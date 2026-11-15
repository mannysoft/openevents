<div>
    <div class="mb-8 flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-6 sm:flex-row sm:items-center">
        @if ($organiser->logoUrl())
            <img src="{{ $organiser->logoUrl() }}" alt="{{ $organiser->name }}" class="h-16 w-16 rounded-full object-cover">
        @endif
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">{{ $organiser->name }}</h1>
            @if ($organiser->about)
                <p class="mt-2 max-w-2xl text-sm text-slate-600">{{ $organiser->about }}</p>
            @endif
        </div>
    </div>

    @if ($events->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-slate-600">
            There are no upcoming events from this organiser.
        </div>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($events as $event)
                <a href="{{ route('events.show', $event) }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md" wire:navigate>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                        {{ $event->start_date->clone()->setTimezone($event->timezone)->format('D j M Y, H:i') }}
                    </p>
                    <h2 class="mt-2 text-lg font-semibold">{{ $event->title }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $event->city }}</p>
                    <p class="mt-4 text-sm font-medium">
                        {{ $event->visibleTickets->isEmpty() ? 'Tickets coming soon' : 'From '.\App\Support\Money::format($event->min_price, $event->currency_code) }}
                    </p>
                </a>
            @endforeach
        </div>

        <div class="mt-8">{{ $events->links() }}</div>
    @endif
</div>
