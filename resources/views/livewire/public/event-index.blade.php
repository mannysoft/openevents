<div>
    <div class="mb-8">
        <h1 class="text-3xl font-semibold tracking-tight">Upcoming events</h1>
        <p class="mt-2 text-slate-600">Tickets sold directly by the organisers.</p>
    </div>

    @if ($events->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <p class="text-slate-600">No events are on sale yet.</p>
            @guest
                <a href="{{ route('register') }}" class="mt-4 inline-block text-sm font-medium text-slate-900 underline">Be the first to sell tickets</a>
            @endguest
        </div>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($events as $event)
                <a href="{{ route('events.show', $event) }}" class="group overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md" wire:navigate>
                    @if ($event->coverImageUrl())
                        <img src="{{ $event->coverImageUrl() }}" alt="{{ $event->title }}" class="h-40 w-full object-cover">
                    @else
                        <div class="flex h-40 items-center justify-center bg-gradient-to-br from-slate-800 to-slate-600 text-2xl font-semibold text-white">
                            {{ Str::limit($event->title, 30) }}
                        </div>
                    @endif

                    <div class="p-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                            {{ $event->start_date->clone()->setTimezone($event->timezone)->format('D j M Y, H:i') }}
                        </p>
                        <h2 class="mt-2 text-lg font-semibold group-hover:underline">{{ $event->title }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ $event->organiser->name }}</p>
                        @if ($event->city)
                            <p class="mt-1 text-sm text-slate-500">{{ $event->venue_name ?? $event->city }}{{ $event->venue_name ? ', '.$event->city : '' }}</p>
                        @endif
                        <p class="mt-4 text-sm font-medium text-slate-900">
                            @if ($event->visibleTickets->isEmpty())
                                Tickets coming soon
                            @elseif ($event->min_price === 0)
                                Free
                            @else
                                From {{ \App\Support\Money::format($event->min_price, $event->currency_code) }}
                            @endif
                        </p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $events->links() }}
        </div>
    @endif
</div>
