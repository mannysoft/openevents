<div>
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm text-slate-500">{{ $event->organiser->name }}</p>
            <h2 class="text-xl font-semibold tracking-tight">{{ $event->title }}</h2>
        </div>
        <button type="button" wire:click="create" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
            New ticket
        </button>
    </div>

    <x-admin.event-nav :event="$event" />

    <x-ui.card title="Tickets">
        @if ($tickets->isEmpty())
            <div class="rounded-lg border border-dashed border-slate-300 px-6 py-12 text-center">
                <p class="text-sm font-medium text-slate-900">No tickets yet</p>
                <p class="mt-1 text-sm text-slate-500">Create your first ticket type to start selling.</p>
                <button type="button" wire:click="create" class="mt-4 rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    New ticket
                </button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[52rem] text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Ticket</th>
                            <th class="px-3 py-2">Price</th>
                            <th class="px-3 py-2">Available</th>
                            <th class="px-3 py-2">Sold</th>
                            <th class="px-3 py-2">Remaining</th>
                            <th class="px-3 py-2">Sales window</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($tickets as $index => $ticket)
                            <tr wire:key="ticket-{{ $ticket->id }}" class="align-top">
                                <td class="px-3 py-3">
                                    <div class="font-medium text-slate-900">{{ $ticket->title }}</div>
                                    @if ($ticket->description)
                                        <div class="mt-1 max-w-sm text-xs text-slate-500">{{ str($ticket->description)->limit(90) }}</div>
                                    @endif
                                    <div class="mt-1 text-xs text-slate-400">Min {{ $ticket->min_per_order }} / max {{ $ticket->max_per_order }} per order</div>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap font-medium">{{ \App\Support\Money::format($ticket->price, $currency) }}</td>
                                <td class="px-3 py-3 whitespace-nowrap text-slate-600">
                                    {{ $ticket->quantity_available === null ? 'Unlimited' : $ticket->quantity_available }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-slate-600">{{ $ticket->quantity_sold }}</td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    @if ($ticket->remaining() === null)
                                        <span class="text-slate-500">Unlimited</span>
                                    @elseif ($ticket->isSoldOut())
                                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700">Sold out</span>
                                    @else
                                        <span class="text-slate-600">{{ $ticket->remaining() }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-xs text-slate-500">
                                    @if ($ticket->sales_start_at || $ticket->sales_end_at)
                                        <div>{{ $ticket->sales_start_at?->format('j M Y, H:i') ?? 'Any time' }}</div>
                                        <div class="text-slate-400">to {{ $ticket->sales_end_at?->format('j M Y, H:i') ?? 'no end' }}</div>
                                    @else
                                        <span class="text-slate-400">Always on sale</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    @if ($ticket->is_hidden)
                                        <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-700">Hidden</span>
                                    @elseif ($ticket->isOnSale())
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">On sale</span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Off sale</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex flex-wrap items-center justify-end gap-1 text-xs">
                                        <button type="button" wire:click="move({{ $ticket->id }}, 'up')" @disabled($index === 0)
                                            class="rounded-md border border-slate-300 px-2 py-1 text-slate-600 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40" title="Move up">↑</button>
                                        <button type="button" wire:click="move({{ $ticket->id }}, 'down')" @disabled($index === $tickets->count() - 1)
                                            class="rounded-md border border-slate-300 px-2 py-1 text-slate-600 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40" title="Move down">↓</button>
                                        <button type="button" wire:click="edit({{ $ticket->id }})"
                                            class="rounded-md border border-slate-300 px-2 py-1 text-slate-700 hover:bg-slate-100">Edit</button>
                                        <button type="button" wire:click="toggleHidden({{ $ticket->id }})"
                                            class="rounded-md border border-slate-300 px-2 py-1 text-slate-700 hover:bg-slate-100">
                                            {{ $ticket->is_hidden ? 'Show' : 'Hide' }}
                                        </button>
                                        <button type="button" wire:click="delete({{ $ticket->id }})"
                                            wire:confirm="Delete this ticket? This cannot be undone."
                                            class="rounded-md border border-rose-300 px-2 py-1 text-rose-700 hover:bg-rose-50">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/40 p-4 sm:items-center">
            <div class="w-full max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold">{{ $editingId ? 'Edit ticket' : 'New ticket' }}</h3>
                    <button type="button" wire:click="cancelForm" class="text-sm text-slate-500 hover:text-slate-700">Close</button>
                </div>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label for="ticket-title" class="block text-sm font-medium text-slate-700">Title</label>
                        <input id="ticket-title" type="text" wire:model="title" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="ticket-description" class="block text-sm font-medium text-slate-700">Description</label>
                        <textarea id="ticket-description" rows="3" wire:model="description" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none"></textarea>
                        @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="ticket-price" class="block text-sm font-medium text-slate-700">Price ({{ $currency }})</label>
                            <input id="ticket-price" type="number" step="0.01" min="0" wire:model="price" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                            @error('price') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="ticket-quantity" class="block text-sm font-medium text-slate-700">Quantity available</label>
                            <input id="ticket-quantity" type="number" min="0" wire:model="quantity_available" placeholder="Unlimited" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                            @error('quantity_available') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="ticket-min" class="block text-sm font-medium text-slate-700">Min / order</label>
                                <input id="ticket-min" type="number" min="1" wire:model="min_per_order" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                                @error('min_per_order') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="ticket-max" class="block text-sm font-medium text-slate-700">Max / order</label>
                                <input id="ticket-max" type="number" min="1" wire:model="max_per_order" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                                @error('max_per_order') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="ticket-sales-start" class="block text-sm font-medium text-slate-700">Sales start</label>
                            <input id="ticket-sales-start" type="datetime-local" wire:model="sales_start_at" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                            @error('sales_start_at') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="ticket-sales-end" class="block text-sm font-medium text-slate-700">Sales end</label>
                            <input id="ticket-sales-end" type="datetime-local" wire:model="sales_end_at" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                            @error('sales_end_at') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="is_hidden" class="h-4 w-4 rounded border-slate-300">
                        Hide this ticket from the public event page
                    </label>
                    @error('is_hidden') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror

                    <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
                        <button type="button" wire:click="cancelForm" class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">Cancel</button>
                        <button type="submit" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                            {{ $editingId ? 'Save changes' : 'Create ticket' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
