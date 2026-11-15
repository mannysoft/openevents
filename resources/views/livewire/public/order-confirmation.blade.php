<div class="mx-auto max-w-2xl">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500">Order {{ $order->reference }}</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $order->event->title }}</h1>
        <p class="mt-1 text-sm text-slate-600">
            {{ $order->event->start_date->clone()->setTimezone($order->event->timezone)->format('l j F Y, H:i') }}
        </p>

        <div class="mt-4">
            @if ($order->isCompleted())
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-800">Payment received</span>
            @elseif ($order->status === \App\Enums\OrderStatus::Reserved)
                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800">Awaiting payment confirmation</span>
            @elseif ($order->status === \App\Enums\OrderStatus::Refunded)
                <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-medium text-slate-700">Refunded</span>
            @else
                <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-medium text-rose-800">Cancelled</span>
            @endif
        </div>

        <dl class="mt-6 grid gap-3 border-t border-slate-200 pt-5 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-slate-500">Name</dt>
                <dd class="font-medium">{{ $order->name() ?: $order->email }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Email</dt>
                <dd class="font-medium">{{ $order->email }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Total</dt>
                <dd class="font-medium">{{ $order->totalLabel() }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Organiser</dt>
                <dd class="font-medium">
                    <a href="{{ route('organisers.show', $order->event->organiser) }}" class="hover:underline" wire:navigate>{{ $order->event->organiser->name }}</a>
                </dd>
            </div>
        </dl>

        @if ($order->status === \App\Enums\OrderStatus::Reserved)
            <div class="mt-6 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p>We are waiting for the payment to be confirmed. This page will update once it clears.</p>
                <button type="button" wire:click="refreshStatus" class="mt-2 rounded-md border border-amber-300 px-3 py-1.5 text-xs font-medium hover:bg-amber-100">
                    Refresh status
                </button>
            </div>
        @endif

        @if ($order->isCompleted())
            <h2 class="mt-8 text-base font-semibold">Your tickets</h2>
            <ul class="mt-3 divide-y divide-slate-100">
                @foreach ($order->attendees as $attendee)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                        <div>
                            <p class="font-medium">{{ $attendee->name() ?: $attendee->email }}</p>
                            <p class="text-slate-500">{{ $attendee->ticket?->title }} &middot; {{ $attendee->reference }}</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('tickets.show', $attendee) }}" class="font-medium text-slate-900 hover:underline" wire:navigate>View ticket</a>
                            <a href="{{ route('tickets.pdf', $attendee) }}" class="text-slate-600 hover:underline">PDF</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="mt-8 border-t border-slate-200 pt-5">
            <a href="{{ route('events.index') }}" class="text-sm text-slate-600 hover:underline" wire:navigate>Browse more events</a>
        </div>
    </div>
</div>
