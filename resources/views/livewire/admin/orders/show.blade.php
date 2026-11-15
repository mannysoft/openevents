<div>
    @php
        $badge = match ($order->status->value) {
            'completed' => 'bg-emerald-100 text-emerald-700',
            'reserved' => 'bg-amber-100 text-amber-700',
            'refunded' => 'bg-rose-100 text-rose-700',
            default => 'bg-slate-200 text-slate-700',
        };
    @endphp

    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('admin.orders.index', $order->event) }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Back to orders</a>
            <div class="mt-1 flex items-center gap-3">
                <h2 class="text-xl font-semibold tracking-tight">{{ $order->reference }}</h2>
                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $badge }}">{{ $order->status->label() }}</span>
            </div>
            <p class="mt-1 text-sm text-slate-500">
                {{ $order->name() ?: $order->email }} &middot; {{ $order->created_at?->format('j M Y, H:i') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($order->status === \App\Enums\OrderStatus::Reserved)
                <button type="button" wire:click="cancelOrder" wire:confirm="Cancel this unpaid order? Attendees will be removed."
                    class="rounded-md border border-rose-300 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">
                    Cancel order
                </button>
            @endif

            <button type="button" wire:click="openRefundModal" @disabled(! $order->isRefundable())
                class="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40">
                Refund
            </button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card title="Items">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[32rem] text-left text-sm">
                        <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="py-2 pr-3">Ticket</th>
                                <th class="py-2 pr-3">Unit price</th>
                                <th class="py-2 pr-3">Qty</th>
                                <th class="py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($items as $item)
                                <tr wire:key="item-{{ $item->id }}">
                                    <td class="py-3 pr-3">
                                        <div class="font-medium text-slate-900">{{ $item->description }}</div>
                                        @if ($item->ticket === null)
                                            <div class="text-xs text-slate-400">Ticket deleted</div>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-3 whitespace-nowrap text-slate-600">{{ \App\Support\Money::format((int) $item->unit_price, $currency) }}</td>
                                    <td class="py-3 pr-3 whitespace-nowrap text-slate-600">{{ $item->quantity }}</td>
                                    <td class="py-3 whitespace-nowrap text-right font-medium">{{ \App\Support\Money::format((int) $item->total, $currency) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t border-slate-200 text-sm">
                            <tr>
                                <td colspan="3" class="py-2 pr-3 text-right text-slate-500">Subtotal</td>
                                <td class="py-2 text-right">{{ \App\Support\Money::format((int) $order->subtotal, $currency) }}</td>
                            </tr>
                            @if ($order->discount_total > 0)
                                <tr>
                                    <td colspan="3" class="py-2 pr-3 text-right text-slate-500">
                                        Discount{{ $order->discountCode ? ' ('.$order->discountCode->code.')' : '' }}
                                    </td>
                                    <td class="py-2 text-right text-emerald-700">-{{ \App\Support\Money::format((int) $order->discount_total, $currency) }}</td>
                                </tr>
                            @endif
                            @if ($order->tax_total > 0)
                                <tr>
                                    <td colspan="3" class="py-2 pr-3 text-right text-slate-500">Tax</td>
                                    <td class="py-2 text-right">{{ \App\Support\Money::format((int) $order->tax_total, $currency) }}</td>
                                </tr>
                            @endif
                            <tr class="border-t border-slate-200">
                                <td colspan="3" class="py-2 pr-3 text-right font-medium text-slate-900">Total</td>
                                <td class="py-2 text-right font-semibold text-slate-900">{{ \App\Support\Money::format((int) $order->total, $currency) }}</td>
                            </tr>
                            @if ($order->refunded_amount > 0)
                                <tr>
                                    <td colspan="3" class="py-2 pr-3 text-right text-slate-500">Refunded</td>
                                    <td class="py-2 text-right text-rose-600">-{{ \App\Support\Money::format((int) $order->refunded_amount, $currency) }}</td>
                                </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
                <p class="mt-3 text-xs text-slate-400">Currency: {{ $currency }}</p>
            </x-ui.card>

            <x-ui.card title="Attendees">
                @if ($attendees->isEmpty())
                    <p class="text-sm text-slate-500">No attendees on this order.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[32rem] text-left text-sm">
                            <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="py-2 pr-3">Reference</th>
                                    <th class="py-2 pr-3">Name</th>
                                    <th class="py-2 pr-3">Email</th>
                                    <th class="py-2 pr-3">Ticket</th>
                                    <th class="py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($attendees as $attendee)
                                    <tr wire:key="attendee-{{ $attendee->id }}">
                                        <td class="py-3 pr-3 whitespace-nowrap">
                                            <a href="{{ route('tickets.show', $attendee) }}" class="font-medium text-slate-900 underline-offset-2 hover:underline">
                                                {{ $attendee->reference }}
                                            </a>
                                        </td>
                                        <td class="py-3 pr-3">{{ $attendee->name() ?: '—' }}</td>
                                        <td class="py-3 pr-3 text-slate-600">{{ $attendee->email ?? '—' }}</td>
                                        <td class="py-3 pr-3 text-slate-600">{{ $attendee->ticket?->title ?? '—' }}</td>
                                        <td class="py-3">
                                            <div class="flex flex-wrap gap-1">
                                                @if ($attendee->is_cancelled)
                                                    <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-700">Cancelled</span>
                                                @endif
                                                @if ($attendee->is_refunded)
                                                    <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700">Refunded</span>
                                                @endif
                                                @if ($attendee->isCheckedIn())
                                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Checked in</span>
                                                @endif
                                                @if (! $attendee->is_cancelled && ! $attendee->is_refunded && ! $attendee->isCheckedIn())
                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">Active</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Summary">
                <dl class="space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Reference</dt>
                        <dd class="font-medium">{{ $order->reference }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Status</dt>
                        <dd><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $badge }}">{{ $order->status->label() }}</span></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Customer</dt>
                        <dd class="text-right">
                            <div class="font-medium">{{ $order->name() ?: '—' }}</div>
                            <div class="text-xs text-slate-500">{{ $order->email }}</div>
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Event</dt>
                        <dd class="text-right">
                            <a href="{{ route('events.show', $order->event) }}" class="font-medium underline-offset-2 hover:underline">{{ $order->event->title }}</a>
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Created</dt>
                        <dd>{{ $order->created_at?->format('j M Y, H:i') ?? '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Paid</dt>
                        <dd>{{ $order->paid_at?->format('j M Y, H:i') ?? '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Refunded at</dt>
                        <dd>{{ $order->refunded_at?->format('j M Y, H:i') ?? '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Cancelled at</dt>
                        <dd>{{ $order->cancelled_at?->format('j M Y, H:i') ?? '—' }}</dd>
                    </div>
                </dl>

                <a href="{{ route('admin.events.edit', [$order->event->organiser, $order->event]) }}"
                   class="mt-4 inline-block text-sm text-slate-600 underline-offset-2 hover:underline">
                    Manage event
                </a>
            </x-ui.card>

            <x-ui.card title="Payment">
                <dl class="space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Payment intent</dt>
                        <dd class="break-all text-right font-mono text-xs">{{ $order->stripe_payment_intent_id ?? '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Connected account</dt>
                        <dd class="break-all text-right font-mono text-xs">{{ $order->stripe_account_id ?? '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Application fee</dt>
                        <dd>{{ \App\Support\Money::format((int) $order->application_fee_amount, $currency) }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Net to organiser</dt>
                        <dd class="font-medium">{{ \App\Support\Money::format($order->netToOrganiserMinor(), $currency) }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 border-t border-slate-200 pt-3">
                        <dt class="text-slate-500">Refunded</dt>
                        <dd class="{{ $order->refunded_amount > 0 ? 'font-medium text-rose-600' : '' }}">
                            {{ \App\Support\Money::format((int) $order->refunded_amount, $currency) }}
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500">Refundable</dt>
                        <dd>{{ \App\Support\Money::format((int) $order->refundableMinor(), $currency) }}</dd>
                    </div>
                </dl>
            </x-ui.card>
        </div>
    </div>

    @if ($showRefundModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/40 p-4 sm:items-center">
            <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-xl">
                <h3 class="text-base font-semibold">Refund order {{ $order->reference }}</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Refundable balance: <span class="font-medium text-slate-900">{{ \App\Support\Money::format((int) $order->refundableMinor(), $currency) }}</span>
                </p>

                <form wire:submit="refund" class="mt-4 space-y-4">
                    <div>
                        <label for="refund-amount" class="block text-sm font-medium text-slate-700">Amount ({{ $currency }})</label>
                        <input id="refund-amount" type="number" step="0.01" min="0.01" wire:model="refundAmount"
                            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('refundAmount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
                        <button type="button" wire:click="closeRefundModal" class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="refund"
                            class="rounded-md bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50">
                            Issue refund
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
