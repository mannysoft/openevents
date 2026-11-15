@use('App\Support\Money')

<div class="space-y-6">
    <div>
        <p class="text-sm text-slate-500">Messaging</p>
        <h1 class="text-xl font-semibold">{{ $event->title }}</h1>
    </div>

    <x-admin.event-nav :event="$event" />

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">
            <x-ui.card title="Compose message">
                <form wire:submit="send" class="space-y-4">
                    <div>
                        <label for="subject" class="block text-sm font-medium text-slate-700">Subject</label>
                        <input id="subject" type="text" wire:model="subject" maxlength="150"
                               class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        @error('subject') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="body" class="block text-sm font-medium text-slate-700">Message</label>
                        <textarea id="body" rows="8" wire:model="body" maxlength="5000"
                                  class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></textarea>
                        @error('body') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="filter" class="block text-sm font-medium text-slate-700">Recipients</label>
                            <select id="filter" wire:model.live="filter"
                                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                <option value="all">All attendees of completed orders</option>
                                <option value="ticket">By ticket type</option>
                                <option value="checked_in">Checked in only</option>
                                <option value="not_checked_in">Not checked in</option>
                            </select>
                            @error('filter') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        @if ($filter === 'ticket')
                            <div>
                                <label for="ticketId" class="block text-sm font-medium text-slate-700">Ticket</label>
                                <select id="ticketId" wire:model.live="ticketId"
                                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                    <option value="">Select a ticket…</option>
                                    @foreach ($tickets as $ticket)
                                        <option value="{{ $ticket->id }}">{{ $ticket->title }} ({{ Money::format($ticket->price, $event->currency_code) }})</option>
                                    @endforeach
                                </select>
                                @error('ticketId') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>

                    <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                        <p class="font-medium">{{ number_format($recipientsCount) }} {{ $recipientsCount === 1 ? 'recipient' : 'recipients' }}</p>
                        @if ($sampleEmails !== [])
                            <p class="mt-1 text-xs text-slate-500">e.g. {{ implode(', ', $sampleEmails) }}</p>
                        @else
                            <p class="mt-1 text-xs text-slate-500">No attendees match this filter yet.</p>
                        @endif
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                                @disabled($recipientsCount === 0)>
                            Queue message
                        </button>
                    </div>
                </form>
            </x-ui.card>
        </div>

        <div>
            <x-ui.card title="Recent messages">
                @if ($messageLogs->isEmpty())
                    <p class="text-sm text-slate-500">No messages sent for this event yet.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($messageLogs as $log)
                            <div class="border-b border-slate-100 pb-3 last:border-0 last:pb-0">
                                <p class="text-sm font-medium">{{ $log->subject }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ number_format($log->recipients_count) }} recipients &middot;
                                    {{ $log->user?->name ?? 'System' }} &middot;
                                    {{ $log->sent_at?->format('d M Y, H:i') ?? '—' }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</div>
