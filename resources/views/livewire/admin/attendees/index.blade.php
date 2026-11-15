<div class="space-y-6">
    <x-admin.event-nav :event="$event" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold">Attendees</h2>
            <p class="text-sm text-slate-500">{{ $event->title }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.attendees.export', [$event, 'csv']) }}"
               class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                Export CSV
            </a>
            <a href="{{ route('admin.attendees.export', [$event, 'xlsx']) }}"
               class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                Export XLSX
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <x-ui.card>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total attendees</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($summary['total']) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Checked in</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ number_format($summary['checked_in']) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Cancelled</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($summary['cancelled']) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Refunded</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($summary['refunded']) }}</p>
        </x-ui.card>
    </div>

    <x-ui.card>
        <div class="grid gap-3 sm:grid-cols-3">
            <div>
                <label for="attendee-search" class="mb-1 block text-xs font-medium text-slate-600">Search</label>
                <input id="attendee-search" type="search" wire:model.live.debounce.300ms="search"
                       placeholder="Name, email or reference"
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
            </div>

            <div>
                <label for="attendee-ticket" class="mb-1 block text-xs font-medium text-slate-600">Ticket</label>
                <select id="attendee-ticket" wire:model.live="ticket"
                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                    <option value="">All tickets</option>
                    @foreach ($tickets as $ticketOption)
                        <option value="{{ $ticketOption->id }}">{{ $ticketOption->title }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="attendee-status" class="mb-1 block text-xs font-medium text-slate-600">Status</label>
                <select id="attendee-status" wire:model.live="status"
                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="checked-in">Checked in</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
            <span>{{ number_format($attendees->total()) }} matching attendee{{ $attendees->total() === 1 ? '' : 's' }}</span>
            <button type="button" wire:click="clearFilters" class="underline hover:text-slate-800">Clear filters</button>
        </div>
    </x-ui.card>

    @error('resend')
        <div class="rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</div>
    @enderror

    <x-ui.card>
        @if ($attendees->isEmpty())
            <div class="py-12 text-center">
                <p class="text-sm font-semibold text-slate-700">No attendees found</p>
                <p class="mt-1 text-sm text-slate-500">
                    Attendees appear here once an order is completed. Try adjusting your filters.
                </p>
                <button type="button" wire:click="clearFilters" class="mt-4 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                    Clear filters
                </button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[56rem] text-left text-sm">
                    <thead class="text-xs uppercase tracking-wide text-slate-500">
                        <tr class="border-b border-slate-200">
                            <th class="px-3 py-2 font-medium">Reference</th>
                            <th class="px-3 py-2 font-medium">Name</th>
                            <th class="px-3 py-2 font-medium">Email</th>
                            <th class="px-3 py-2 font-medium">Ticket</th>
                            <th class="px-3 py-2 font-medium">Order</th>
                            <th class="px-3 py-2 font-medium">Status</th>
                            <th class="px-3 py-2 font-medium">Checked in</th>
                            <th class="px-3 py-2 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($attendees as $attendee)
                            <tr wire:key="attendee-{{ $attendee->id }}">
                                <td class="px-3 py-2 font-mono text-xs text-slate-700">{{ $attendee->reference }}</td>
                                <td class="px-3 py-2 font-medium">{{ $attendee->name() ?: '—' }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $attendee->email ?: '—' }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $attendee->ticket?->title ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    @if ($attendee->order)
                                        <a href="{{ route('admin.orders.show', $attendee->order) }}"
                                           class="font-mono text-xs text-slate-700 underline hover:text-slate-900">
                                            {{ $attendee->order->reference }}
                                        </a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if ($attendee->is_refunded)
                                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700">Refunded</span>
                                    @elseif ($attendee->is_cancelled)
                                        <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-700">Cancelled</span>
                                    @elseif ($attendee->isCheckedIn())
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Checked in</span>
                                    @else
                                        <span class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-700">Active</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-slate-600">
                                    {{ $attendee->checked_in_at?->format('d M Y H:i') ?? '—' }}
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                        <button type="button" wire:click="edit({{ $attendee->id }})" class="underline hover:text-slate-900">
                                            Edit
                                        </button>
                                        <a href="{{ route('tickets.show', $attendee) }}" target="_blank" rel="noopener" class="underline hover:text-slate-900">
                                            Ticket
                                        </a>
                                        <button type="button" wire:click="resendTicket({{ $attendee->id }})"
                                                wire:confirm="Resend this ticket to {{ $attendee->email }}?"
                                                class="underline hover:text-slate-900">
                                            Resend
                                        </button>
                                        @if ($attendee->is_cancelled)
                                            <button type="button" wire:click="restoreAttendee({{ $attendee->id }})" class="underline hover:text-slate-900">
                                                Restore
                                            </button>
                                        @else
                                            <button type="button" wire:click="cancelAttendee({{ $attendee->id }})"
                                                    wire:confirm="Cancel this attendee's ticket?"
                                                    class="underline text-rose-700 hover:text-rose-900">
                                                Cancel
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $attendees->links() }}
            </div>
        @endif
    </x-ui.card>

    @if ($showEditModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4" wire:click.self="closeEditModal">
            <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl">
                <h3 class="text-base font-semibold">Edit attendee</h3>

                <form wire:submit="save" class="mt-4 space-y-3">
                    <div>
                        <label for="edit-first-name" class="mb-1 block text-xs font-medium text-slate-600">First name</label>
                        <input id="edit-first-name" type="text" wire:model="first_name"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
                        @error('first_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="edit-last-name" class="mb-1 block text-xs font-medium text-slate-600">Last name</label>
                        <input id="edit-last-name" type="text" wire:model="last_name"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
                        @error('last_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="edit-email" class="mb-1 block text-xs font-medium text-slate-600">Email</label>
                        <input id="edit-email" type="email" wire:model="email"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
                        @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeEditModal"
                                class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                            Cancel
                        </button>
                        <button type="submit"
                                class="rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                            Save changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
