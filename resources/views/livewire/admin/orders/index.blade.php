<div>
    <div class="mb-4">
        <p class="text-sm text-slate-500">{{ $event->organiser->name }}</p>
        <h2 class="text-xl font-semibold tracking-tight">{{ $event->title }}</h2>
    </div>

    <x-admin.event-nav :event="$event" />

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-ui.card>
            <p class="text-xs uppercase tracking-wide text-slate-500">Gross completed revenue</p>
            <p class="mt-2 text-2xl font-semibold">{{ \App\Support\Money::format($grossCompletedMinor, $currency) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs uppercase tracking-wide text-slate-500">Orders</p>
            <p class="mt-2 text-2xl font-semibold">{{ number_format($orderCount) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs uppercase tracking-wide text-slate-500">Refunded</p>
            <p class="mt-2 text-2xl font-semibold text-rose-600">{{ \App\Support\Money::format($refundedMinor, $currency) }}</p>
        </x-ui.card>
    </div>

    <x-ui.card>
        <div class="mb-4 flex flex-wrap items-end gap-3">
            <div>
                <label for="order-status" class="block text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
                <select id="order-status" wire:model.live="status" class="mt-1 rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    <option value="all">All statuses</option>
                    @foreach ($statuses as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[16rem] flex-1">
                <label for="order-search" class="block text-xs font-medium uppercase tracking-wide text-slate-500">Search</label>
                <input id="order-search" type="search" wire:model.live.debounce.300ms="search"
                    placeholder="Reference, email or name"
                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
            </div>

            <div>
                <label for="order-direction" class="block text-xs font-medium uppercase tracking-wide text-slate-500">Date ordering</label>
                <select id="order-direction" wire:model.live="direction" class="mt-1 rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    <option value="desc">Newest first</option>
                    <option value="asc">Oldest first</option>
                </select>
            </div>

            @if ($status !== 'all' || $search !== '')
                <button type="button" wire:click="clearFilters" class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                    Clear
                </button>
            @endif
        </div>

        @if ($orders->isEmpty())
            <div class="rounded-lg border border-dashed border-slate-300 px-6 py-12 text-center">
                <p class="text-sm font-medium text-slate-900">No orders found</p>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $status === 'all' && $search === '' ? 'Orders will appear here once someone checks out.' : 'Try adjusting the filters above.' }}
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[52rem] text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Reference</th>
                            <th class="px-3 py-2">Customer</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Items</th>
                            <th class="px-3 py-2">Total</th>
                            <th class="px-3 py-2">Paid</th>
                            <th class="px-3 py-2">Refunded</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($orders as $order)
                            @php
                                $badge = match ($order->status->value) {
                                    'completed' => 'bg-emerald-100 text-emerald-700',
                                    'reserved' => 'bg-amber-100 text-amber-700',
                                    'refunded' => 'bg-rose-100 text-rose-700',
                                    default => 'bg-slate-200 text-slate-700',
                                };
                            @endphp
                            <tr wire:key="order-{{ $order->id }}" class="align-top">
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-slate-900 underline-offset-2 hover:underline">
                                        {{ $order->reference }}
                                    </a>
                                    <div class="text-xs text-slate-400">{{ $order->created_at?->format('j M Y, H:i') }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="font-medium text-slate-900">{{ $order->name() ?: '—' }}</div>
                                    <div class="text-xs text-slate-500">{{ $order->email }}</div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $badge }}">{{ $order->status->label() }}</span>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-slate-600">{{ $order->items_count }}</td>
                                <td class="px-3 py-3 whitespace-nowrap font-medium">{{ \App\Support\Money::format((int) $order->total, $order->currency_code) }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-slate-600">{{ $order->paid_at?->format('j M Y, H:i') ?? '—' }}</td>
                                <td class="px-3 py-3 whitespace-nowrap {{ $order->refunded_amount > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                    {{ \App\Support\Money::format((int) $order->refunded_amount, $order->currency_code) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
