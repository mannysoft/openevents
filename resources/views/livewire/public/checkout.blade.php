<div class="mx-auto max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('events.show', $event) }}" class="text-sm text-slate-500 hover:underline" wire:navigate>&larr; {{ $event->title }}</a>
    </div>

    <ol class="mb-6 flex items-center gap-3 text-sm">
        @foreach (['Tickets', 'Details', 'Payment'] as $index => $label)
            <li class="flex items-center gap-2 {{ $step === $index + 1 ? 'font-semibold text-slate-900' : 'text-slate-400' }}">
                <span class="flex h-6 w-6 items-center justify-center rounded-full border {{ $step === $index + 1 ? 'border-slate-900' : 'border-slate-300' }}">{{ $index + 1 }}</span>
                {{ $label }}
            </li>
            @if (! $loop->last)
                <li class="h-px w-8 bg-slate-300"></li>
            @endif
        @endforeach
    </ol>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @if ($step === 1)
            <h1 class="text-xl font-semibold">Choose your tickets</h1>

            @error('quantities')
                <p class="mt-3 rounded-md bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</p>
            @enderror

            <div class="mt-5 space-y-4">
                @forelse ($tickets as $ticket)
                    <div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 p-4">
                        <div>
                            <p class="font-medium">{{ $ticket->title }}</p>
                            @if ($ticket->description)
                                <p class="text-sm text-slate-500">{{ $ticket->description }}</p>
                            @endif
                            <p class="mt-1 text-sm text-slate-600">{{ $ticket->priceLabel() }}</p>
                            @if ($ticket->remaining() !== null)
                                <p class="text-xs text-slate-400">{{ $ticket->remaining() }} remaining</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="decrement({{ $ticket->id }})" class="h-9 w-9 rounded-md border border-slate-300 text-lg leading-none hover:bg-slate-100">&minus;</button>
                            <span class="w-8 text-center text-sm font-medium">{{ (int) ($quantities[$ticket->id] ?? 0) }}</span>
                            <button type="button" wire:click="increment({{ $ticket->id }})" class="h-9 w-9 rounded-md border border-slate-300 text-lg leading-none hover:bg-slate-100">&plus;</button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-600">There are no tickets on sale right now.</p>
                @endforelse
            </div>

            <div class="mt-6 flex items-center justify-between border-t border-slate-200 pt-5">
                <p class="text-sm text-slate-600">
                    Subtotal
                    <span class="ml-2 text-base font-semibold text-slate-900">{{ \App\Support\Money::format($subtotal, $currency) }}</span>
                </p>
                <button type="button" wire:click="continueToDetails" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700" @disabled($tickets->isEmpty())>
                    Continue
                </button>
            </div>
        @elseif ($step === 2)
            <h1 class="text-xl font-semibold">Your details</h1>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">First name</label>
                    <input type="text" wire:model="firstName" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    @error('firstName') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Last name</label>
                    <input type="text" wire:model="lastName" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    @error('lastName') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Email</label>
                    <input type="email" wire:model="email" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    @error('email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <h2 class="mt-8 text-base font-semibold">Attendees</h2>

            <div class="mt-4 space-y-5">
                @foreach ($attendees as $index => $attendee)
                    @php($ticket = $tickets->firstWhere('id', (int) $attendee['ticket_id']))
                    <fieldset class="rounded-lg border border-slate-200 p-4">
                        <legend class="px-1 text-sm font-medium">{{ $ticket?->title ?? 'Ticket' }}</legend>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">First name</label>
                                <input type="text" wire:model="attendees.{{ $index }}.first_name" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                @error("attendees.{$index}.first_name") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Last name</label>
                                <input type="text" wire:model="attendees.{{ $index }}.last_name" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                @error("attendees.{$index}.last_name") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-xs font-medium text-slate-600">Email</label>
                                <input type="email" wire:model="attendees.{{ $index }}.email" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                @error("attendees.{$index}.email") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            @foreach ($event->questions as $question)
                                <div class="sm:col-span-2">
                                    <label class="mb-1 block text-xs font-medium text-slate-600">
                                        {{ $question->title }}
                                        @if ($question->is_required)<span class="text-rose-600">*</span>@endif
                                    </label>

                                    @if ($question->type === \App\Enums\QuestionType::Select)
                                        <select wire:model="attendees.{{ $index }}.answers.{{ $question->id }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                            <option value="">Choose…</option>
                                            @foreach (($question->options ?? []) as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($question->type === \App\Enums\QuestionType::Checkbox)
                                        <label class="flex items-center gap-2 text-sm">
                                            <input type="checkbox" wire:model="attendees.{{ $index }}.answers.{{ $question->id }}" class="rounded border-slate-300">
                                            Yes
                                        </label>
                                    @else
                                        <input type="text" wire:model="attendees.{{ $index }}.answers.{{ $question->id }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                    @endif

                                    @error("attendees.{$index}.answers.{$question->id}") <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            </div>

            <div class="mt-6">
                <label class="mb-1 block text-sm font-medium">Discount code</label>
                <input type="text" wire:model="discountCode" placeholder="Optional" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm sm:w-64">
                @error('discountCode') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            @error('payment')
                <p class="mt-4 rounded-md bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</p>
            @enderror

            <div class="mt-6 flex items-center justify-between border-t border-slate-200 pt-5">
                <button type="button" wire:click="back" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-100">Back</button>
                <button type="button" wire:click="pay" wire:loading.attr="disabled" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-60">
                    <span wire:loading.remove>Continue to payment</span>
                    <span wire:loading>Preparing…</span>
                </button>
            </div>
        @else
            <h1 class="text-xl font-semibold">Payment</h1>
            <p class="mt-1 text-sm text-slate-600">
                Order {{ $orderReference }} &middot; {{ \App\Support\Money::format($total, $event->currency_code) }}
            </p>

            <div wire:ignore id="payment-element" class="mt-6"></div>
            <p id="payment-error" class="mt-3 text-sm text-rose-600"></p>

            <div class="mt-6 flex items-center justify-between border-t border-slate-200 pt-5">
                <button type="button" wire:click="back" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-100">Back</button>
                <button type="button" id="pay-button" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-60">
                    Pay {{ \App\Support\Money::format($total, $event->currency_code) }}
                </button>
            </div>

            @script
                <script>
                    (function () {
                        const secret = @js($clientSecret);
                        const returnUrl = @js(route('orders.confirmation', $orderReference));

                        if (! secret || window.__stripePaymentIntent === secret) return;

                        window.__stripePaymentIntent = secret;

                        const stripe = Stripe(@js(config('services.stripe.key')));
                        const elements = stripe.elements({ clientSecret: secret });
                        elements.create('payment').mount('#payment-element');

                        const button = document.getElementById('pay-button');

                        button.addEventListener('click', async () => {
                            button.disabled = true;

                            const { error } = await stripe.confirmPayment({
                                elements,
                                confirmParams: { return_url: returnUrl },
                                redirect: 'if_required',
                            });

                            if (error) {
                                button.disabled = false;
                                document.getElementById('payment-error').textContent = error.message;
                                return;
                            }

                            window.location.href = returnUrl;
                        });
                    })();
                </script>
            @endscript
        @endif
    </div>
</div>
