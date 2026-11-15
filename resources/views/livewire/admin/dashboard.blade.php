@use('App\Support\Money')

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">Performance across the organisers you manage.</p>
        <div class="flex gap-2">
            <a href="{{ route('admin.payouts.index') }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-100">Payouts</a>
            <a href="{{ route('admin.organisers.index') }}" class="rounded-md bg-slate-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-800">Manage organisers</a>
        </div>
    </div>

    @if ($needsOnboarding)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-semibold">Finish Stripe onboarding</p>
            <p class="mt-1">These organisers cannot accept payments yet. Complete their Stripe Connect setup before publishing events.</p>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($organisers->filter(fn ($organiser) => ! $organiser->stripeReady()) as $organiser)
                    <a href="{{ route('admin.organisers.stripe', $organiser) }}"
                       class="rounded-md border border-amber-300 bg-white px-2.5 py-1 text-xs font-medium text-amber-900 hover:bg-amber-100">
                        {{ $organiser->name }} &rarr;
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Gross revenue</p>
            <div class="mt-2 space-y-1 text-2xl font-semibold">
                @foreach ($grossRevenue as $line)
                    <p>{{ $line }}</p>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-slate-500">Completed orders only</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Platform fees</p>
            <div class="mt-2 space-y-1 text-2xl font-semibold">
                @foreach ($platformFees as $line)
                    <p>{{ $line }}</p>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-slate-500">Retained by the platform</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Net to organisers</p>
            <div class="mt-2 space-y-1 text-2xl font-semibold">
                @foreach ($netToOrganisers as $line)
                    <p>{{ $line }}</p>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-slate-500">Settles to connected Stripe accounts</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Tickets sold</p>
            <p class="mt-2 text-2xl font-semibold">{{ number_format($ticketsSold) }}</p>
            <p class="mt-2 text-xs text-slate-500">Non-cancelled attendees on completed orders</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Orders</p>
            <p class="mt-2 text-2xl font-semibold">{{ number_format($ordersCount) }}</p>
            <p class="mt-2 text-xs text-slate-500">Completed orders</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Upcoming events</p>
            <p class="mt-2 text-2xl font-semibold">{{ number_format($upcomingEventsCount) }}</p>
            <p class="mt-2 text-xs text-slate-500">Starts from today onwards</p>
        </div>
    </div>

    @if ($multipleCurrencies)
        <p class="text-xs text-slate-500">Totals are shown per currency because your organisers settle in more than one currency.</p>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.card title="Recent orders">
                @if ($recentOrders->isEmpty())
                    <p class="text-sm text-slate-500">No completed orders yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="py-2 pr-4 font-medium">Reference</th>
                                    <th class="py-2 pr-4 font-medium">Event</th>
                                    <th class="py-2 pr-4 font-medium">Customer</th>
                                    <th class="py-2 pr-4 text-right font-medium">Total</th>
                                    <th class="py-2 font-medium">Paid</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($recentOrders as $order)
                                    <tr>
                                        <td class="py-2 pr-4 font-medium">
                                            <a href="{{ route('admin.orders.show', $order) }}" class="underline-offset-2 hover:underline">{{ $order->reference }}</a>
                                        </td>
                                        <td class="py-2 pr-4 text-slate-600">{{ $order->event?->title ?? 'Deleted event' }}</td>
                                        <td class="py-2 pr-4 text-slate-600">
                                            <span class="block">{{ $order->name() ?: 'Guest' }}</span>
                                            <span class="text-xs text-slate-500">{{ $order->email }}</span>
                                        </td>
                                        <td class="py-2 pr-4 text-right font-medium">{{ Money::format($order->total, $order->currency_code) }}</td>
                                        <td class="py-2 text-slate-600">{{ $order->paid_at?->format('d M Y') ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card title="Upcoming events">
                @forelse ($upcomingEvents as $event)
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 py-3 first:pt-0 last:border-0 last:pb-0">
                        <div>
                            <a href="{{ route('admin.events.edit', [$event->organiser, $event]) }}" class="font-medium underline-offset-2 hover:underline">{{ $event->title }}</a>
                            <p class="text-xs text-slate-500">{{ $event->organiser?->name }}</p>
                        </div>
                        <div class="text-right text-sm">
                            <p class="text-slate-700">{{ $event->start_date?->format('d M Y, H:i') ?? 'Date TBC' }}</p>
                            <p class="text-xs text-slate-500">{{ number_format($event->tickets_sold_count) }} tickets sold</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No upcoming events scheduled.</p>
                @endforelse
            </x-ui.card>
        </div>

        <x-ui.card title="Your organisers">
            @forelse ($organisers as $organiser)
                <div class="border-b border-slate-100 py-3 first:pt-0 last:border-0 last:pb-0">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="font-medium">{{ $organiser->name }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $organiser->currency_code }} &middot; {{ number_format($organiser->events_count) }} events
                            </p>
                        </div>
                        @if ($organiser->stripeReady())
                            <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">Stripe ready</span>
                        @else
                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">Stripe incomplete</span>
                        @endif
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                        <a href="{{ route('admin.events.index', $organiser) }}" class="rounded-md border border-slate-300 px-2.5 py-1 text-slate-700 hover:bg-slate-100">Events</a>
                        <a href="{{ route('admin.organisers.stripe', $organiser) }}" class="rounded-md border border-slate-300 px-2.5 py-1 text-slate-700 hover:bg-slate-100">Stripe settings</a>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">
                    You have no organisers yet.
                    <a href="{{ route('admin.organisers.create') }}" class="font-medium text-slate-900 underline">Create your first organiser</a>.
                </p>
            @endforelse
        </x-ui.card>
    </div>
</div>
