<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold">Your organisers</h1>
            <p class="mt-1 text-sm text-slate-500">Each organiser has its own events, payouts and Stripe account.</p>
        </div>

        <a href="{{ route('admin.organisers.create') }}" wire:navigate
           class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            New organiser
        </a>
    </div>

    @if ($organisers->isEmpty())
        <x-ui.card>
            <p class="text-sm text-slate-600">You have no organisers yet. Create one to start selling tickets.</p>
        </x-ui.card>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($organisers as $organiser)
                <x-ui.card wire:key="organiser-{{ $organiser->id }}" class="flex flex-col justify-between">
                    <div>
                        <div class="flex items-start gap-3">
                            @if ($organiser->logoUrl())
                                <img src="{{ $organiser->logoUrl() }}" alt="{{ $organiser->name }}"
                                     class="h-12 w-12 rounded-lg border border-slate-200 object-cover">
                            @else
                                <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-900 text-base font-semibold text-white">
                                    {{ strtoupper(substr($organiser->name, 0, 1)) }}
                                </span>
                            @endif

                            <div class="min-w-0">
                                <h2 class="truncate text-base font-semibold">{{ $organiser->name }}</h2>
                                <p class="truncate text-sm text-slate-500">{{ $organiser->email }}</p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-700">
                                {{ $organiser->events_count }} {{ \Illuminate\Support\Str::plural('event', $organiser->events_count) }}
                            </span>

                            @if ($organiser->stripeReady())
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-800">Stripe connected</span>
                            @else
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-800">Stripe not connected</span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4 text-sm">
                        <a href="{{ route('admin.events.index', $organiser) }}" wire:navigate
                           class="font-medium text-slate-900 hover:underline">
                            Events
                        </a>
                        <a href="{{ route('admin.organisers.edit', $organiser) }}" wire:navigate
                           class="text-slate-600 hover:text-slate-900 hover:underline">
                            Edit
                        </a>

                        @if ($organiser->stripeReady())
                            <a href="{{ route('admin.organisers.stripe', $organiser) }}" wire:navigate
                               class="ms-auto text-slate-600 hover:text-slate-900 hover:underline">
                                Stripe settings
                            </a>
                        @else
                            <a href="{{ route('admin.organisers.stripe', $organiser) }}" wire:navigate
                               class="ms-auto rounded-md bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-700">
                                Connect Stripe
                            </a>
                        @endif
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif
</div>
