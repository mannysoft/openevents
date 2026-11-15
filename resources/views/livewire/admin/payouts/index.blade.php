@use('App\Support\Money')

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">
            Completed ticket sales for {{ $selectedOrganiser?->name ?? 'all organisers' }}.
        </p>
    </div>

    <x-ui.card>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="organiser" class="block text-sm font-medium text-slate-700">Organiser</label>
                <select id="organiser" wire:model.live="organiserId"
                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">All organisers</option>
                    @foreach ($organisers as $organiser)
                        <option value="{{ $organiser->id }}">{{ $organiser->name }} ({{ $organiser->currency_code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="block text-sm font-medium text-slate-700">From</label>
                <input id="from" type="date" wire:model.live="from"
                       class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="to" class="block text-sm font-medium text-slate-700">To</label>
                <input id="to" type="date" wire:model.live="to"
                       class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Payout breakdown">
        @if ($rows->isEmpty())
            <p class="text-sm text-slate-500">No completed orders were paid in this period.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="py-2 pr-4 font-medium">Event</th>
                            <th class="py-2 pr-4 text-right font-medium">Orders</th>
                            <th class="py-2 pr-4 text-right font-medium">Tickets sold</th>
                            <th class="py-2 pr-4 text-right font-medium">Gross</th>
                            <th class="py-2 pr-4 text-right font-medium">Platform fee</th>
                            <th class="py-2 pr-4 text-right font-medium">Net</th>
                            <th class="py-2 text-right font-medium">Refunded</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $row)
                            <tr>
                                <td class="py-2 pr-4">
                                    @if ($row['event'])
                                        <a href="{{ route('admin.orders.index', $row['event']) }}" class="font-medium underline-offset-2 hover:underline">{{ $row['title'] }}</a>
                                        <p class="text-xs text-slate-500">{{ $row['event']->organiser?->name }} &middot; {{ $row['currency'] }}</p>
                                    @else
                                        <span class="text-slate-600">{{ $row['title'] }}</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-4 text-right">{{ number_format($row['orders']) }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($row['tickets']) }}</td>
                                <td class="py-2 pr-4 text-right">{{ Money::format($row['gross'], $row['currency']) }}</td>
                                <td class="py-2 pr-4 text-right">{{ Money::format($row['fees'], $row['currency']) }}</td>
                                <td class="py-2 pr-4 text-right font-medium">{{ Money::format($row['net'], $row['currency']) }}</td>
                                <td class="py-2 text-right text-slate-600">{{ Money::format($row['refunded'], $row['currency']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-slate-300 text-sm font-semibold">
                        @foreach ($totals as $total)
                            <tr>
                                <td class="py-2 pr-4">Total &middot; {{ $total['currency'] }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($total['orders']) }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($total['tickets']) }}</td>
                                <td class="py-2 pr-4 text-right">{{ Money::format($total['gross'], $total['currency']) }}</td>
                                <td class="py-2 pr-4 text-right">{{ Money::format($total['fees'], $total['currency']) }}</td>
                                <td class="py-2 pr-4 text-right">{{ Money::format($total['net'], $total['currency']) }}</td>
                                <td class="py-2 text-right">{{ Money::format($total['refunded'], $total['currency']) }}</td>
                            </tr>
                        @endforeach
                    </tfoot>
                </table>
            </div>
        @endif
    </x-ui.card>

    <div class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-600 shadow-sm">
        <p class="font-medium text-slate-900">How payouts work</p>
        <p class="mt-1">
            Ticket sales settle directly to each organiser's connected Stripe account. The platform fee is retained
            automatically on every charge, so the net column reflects what the organiser receives.
        </p>
    </div>
</div>
