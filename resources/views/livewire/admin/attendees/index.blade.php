<div>
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm text-slate-500">{{ $event->organiser->name }}</p>
            <h2 class="text-xl font-semibold tracking-tight">{{ $event->title }}</h2>
            <p class="text-sm text-slate-500">Attendees</p>
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

    <x-admin.event-nav :event="$event" />

    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <x-ui.card>
            <p class="text-xs uppercase tracking-wide text-slate-500">Total attendees</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($summary['total']) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs uppercase tracking-wide text-slate-500">Checked in</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($summary['checked_in']) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs uppercase tracking-wide text-slate-500">Cancelled</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($summary['cancelled']) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs uppercase tracking-wide text-slate-500">Refunded</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($summary['refunded']) }}</p>
        </x-ui.card>
    </div>

    <x-ui.card class="mb-4">
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="attendee-search" class="block text-sm font-medium text-slate-700">Search</label>
                <input id="attendee-search" type="search" wire:model.live.debounce.300ms="search"
                       placeholder="Name, email or reference"
                       class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
            </div>

            <div>
                <label for="attendee-ticket" class="block text-sm font-medium text-slate-700">Ticket</label>
                <select id="attendee-ticket" wire:model.live="ticket"
                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    <option value="">All tickets</option>
                    @foreach ($tickets as $ticketOption)
                        <option value="{{ $ticketOption->id }}">{{ $ticketOption->title }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="attendee-status" class="block text-sm font-medium text-slate-700">Status</label>
                <select id="attendee-status" wire:model.live="status"
                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
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
        <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</div>
    @enderror

    <x-ui.card>
        @if ($attendees->isEmpty())
            <div class="rounded-lg border border-dashed border-slate-300 px-6 py-12 text-center">
                <p class="text-sm font-medium text-slate-900">No attendees found</p>
                <p class="mt-1 text-sm text-slate-500">
                    Attendees appear once their order is completed. Try adjusting the filters.
                </p>
                <button type="button" wire:click="clearFilters"
                        class="mt-4 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                    Clear filters
                </button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[60rem] text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Reference</th>
                            <th class="px-3 py-2">Name</th>
                            <th class="px-3 py-2">Email</th>
                            <th class="px-3 py-2">Ticket</th>
                            <th class="px-3 py-2">Order</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Checked in</th>
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($attendees as $attendee)
                            <tr wire:key="attendee-{{ $attendee->id }}" class="align-top">
                                <td class="px-3 py-3 whitespace-nowrap font-mono text-xs text-slate-600">{{ $attendee->reference }}</td>
                                <td class="px-3 py-3 font-medium text-slate-900">{{ $attendee->name() ?: '—' }}</td>
                                <td class="px-3 py-3 text-slate-600">{{ $attendee->email ?: '—' }}</td>
                                <td class="px-3 py-3 text-slate-600">{{ $attendee->ticket?->title ?? '—' }}</td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    @if ($attendee->order)
                                        <a href="{{ route('admin.orders.show', $attendee->order) }}"
                                           class="font-mono text-xs text-slate-700 underline hover:text-slate-900">
                                            {{ $attendee->order->reference }}
                                        </a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
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
                                <td class="px-3 py-3 whitespace-nowrap text-xs text-slate-500">
                                    {{ $attendee->checked_in_at?->format('j M Y, H:i') ?? '—' }}
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex flex-wrap items-center justify-end gap-1 text-xs">
                                        <button type="button" wire:click="edit({{ $attendee->id }})"
                                                class="rounded-md border border-slate-300 px-2 py-1 text-slate-700 hover:bg-slate-100">
                                            Edit
                                        </button>
                                        <a href="{{ route('tickets.show', $attendee) }}" target="_blank" rel="noopener"
                                           class="rounded-md border border-slate-300 px-2 py-1 text-slate-700 hover:bg-slate-100">
                                            Ticket
                                        </a>
                                        <button type="button" wire:click="resendTicket({{ $attendee->id }})"
                                                wire:confirm="Resend this ticket to {{ $attendee->email }}?"
                                                class="rounded-md border border-slate-300 px-2 py-1 text-slate-700 hover:bg-slate-100">
                                            Resend
                                        </button>
                                        @if ($attendee->is_cancelled)
                                            <button type="button" wire:click="restoreAttendee({{ $attendee->id }})"
                                                    class="rounded-md border border-slate-300 px-2 py-1 text-slate-700 hover:bg-slate-100">
                                                Restore
                                            </button>
                                        @else
                                            <button type="button" wire:click="cancelAttendee({{ $attendee->id }})"
                                                    wire:confirm="Cancel this attendee's ticket?"
                                                    class="rounded-md border border-rose-300 px-2 py-1 text-rose-700 hover:bg-rose-50">
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
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/40 p-4 sm:items-center">
            <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold">Edit attendee</h3>
                    <button type="button" wire:click="closeEditModal" class="text-sm text-slate-500 hover:text-slate-700">Close</button>
                </div>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label for="attendee-first-name" class="block text-sm font-medium text-slate-700">First name</label>
                        <input id="attendee-first-name" type="text" wire:model="first_name"
                               class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('first_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="attendee-last-name" class="block text-sm font-medium text-slate-700">Last name</label>
                        <input id="attendee-last-name" type="text" wire:model="last_name"
                               class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('last_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="attendee-email" class="block text-sm font-medium text-slate-700">Email</label>
                        <input id="attendee-email" type="email" wire:model="email"
                               class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
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
