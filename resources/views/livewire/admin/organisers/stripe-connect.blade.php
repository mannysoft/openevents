<div class="mx-auto max-w-3xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold">Stripe Connect</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $organiser->name }}</p>
        </div>

        <div class="flex items-center gap-3 text-sm">
            <a href="{{ route('admin.organisers.index') }}" wire:navigate class="text-slate-600 hover:text-slate-900">Back to organisers</a>
            <a href="{{ route('admin.events.index', $organiser) }}" wire:navigate class="text-slate-600 hover:text-slate-900">Events</a>
            <a href="{{ route('admin.organisers.edit', $organiser) }}" wire:navigate class="text-slate-600 hover:text-slate-900">Edit organiser</a>
        </div>
    </div>

    <div class="space-y-4">
        <x-ui.card title="Account status">
            @if (! $organiser->stripe_account_id)
                <p class="text-sm text-slate-600">
                    This organiser is not connected to Stripe yet. Connect an account to collect payments for its events.
                </p>
            @else
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex items-center justify-between gap-4 py-3 first:pt-0">
                        <dt class="text-slate-500">Account ID</dt>
                        <dd class="font-mono text-xs text-slate-900">{{ $organiser->stripe_account_id }}</dd>
                    </div>

                    <div class="flex items-center justify-between gap-4 py-3">
                        <dt class="text-slate-500">Charges enabled</dt>
                        <dd>
                            @if ($organiser->stripe_charges_enabled)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Yes</span>
                            @else
                                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-800">No</span>
                            @endif
                        </dd>
                    </div>

                    <div class="flex items-center justify-between gap-4 py-3">
                        <dt class="text-slate-500">Details submitted</dt>
                        <dd>
                            @if ($organiser->stripe_details_submitted)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Yes</span>
                            @else
                                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-800">No</span>
                            @endif
                        </dd>
                    </div>

                    <div class="flex items-center justify-between gap-4 py-3 last:pb-0">
                        <dt class="text-slate-500">Onboarded</dt>
                        <dd class="text-slate-900">
                            {{ $organiser->stripe_onboarded_at?->format('j M Y, H:i') ?? '—' }}
                        </dd>
                    </div>
                </dl>
            @endif

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="button" wire:click="connect" wire:loading.attr="disabled"
                        class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-50">
                    {{ $organiser->stripe_account_id ? 'Continue onboarding' : 'Connect Stripe' }}
                </button>

                <button type="button" wire:click="refresh" wire:loading.attr="disabled"
                        class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 disabled:opacity-50">
                    Refresh status
                </button>

                @if ($organiser->stripeReady())
                    <button type="button" wire:click="dashboard"
                            class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                        Open Stripe dashboard
                    </button>
                @endif
            </div>

            <p class="mt-4 text-sm text-slate-500">
                Status updates automatically when Stripe reports a change. Use
                <span class="font-medium text-slate-700">Refresh status</span> if the information
                looks stale, for example after finishing onboarding in another tab.
            </p>
        </x-ui.card>

        @if (! $organiser->stripe_account_id)
            <x-ui.card title="How onboarding works">
                <ol class="space-y-3 text-sm text-slate-600">
                    <li>
                        <span class="font-medium text-slate-900">1. Connect Stripe.</span>
                        A Stripe Express account is created for {{ $organiser->name }}. Nothing is
                        charged at this point.
                    </li>
                    <li>
                        <span class="font-medium text-slate-900">2. Complete Stripe's form.</span>
                        On Stripe's site you provide business details, a bank account for payouts
                        and identity documents for the account representative.
                    </li>
                    <li>
                        <span class="font-medium text-slate-900">3. Come back here.</span>
                        When card payments are active, sales transfer to this organiser's account.
                        Until then, events can still sell tickets and the platform holds the money
                        for the organiser.
                    </li>
                </ol>
            </x-ui.card>
        @elseif (! $organiser->stripeReady())
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold">Stripe still needs information</p>
                <p class="mt-1">
                    Card payments are not active yet, so events cannot be published and checkout
                    stays disabled. Continue onboarding to supply the outstanding requirements,
                    then refresh the status.
                </p>
            </div>
        @endif

        @if ($organiser->stripe_account_id)
            <x-ui.card title="Outstanding requirements">
                @if ($requirements['disabled_reason'])
                    <p class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        Stripe has restricted this account: {{ \Illuminate\Support\Str::headline(str_replace(['.', '_'], ' ', $requirements['disabled_reason'])) }}
                    </p>
                @endif

                @if (empty($requirements['currently_due']) && empty($requirements['eventually_due']))
                    <p class="text-sm text-slate-600">No outstanding requirements. You are all set.</p>
                @else
                    @php($rendered = false)

                    @foreach (['currently_due' => 'Required now', 'eventually_due' => 'Required later'] as $bucket => $heading)
                        @if (! empty($requirements[$bucket]))
                            <h3 class="text-sm font-medium text-slate-900 {{ $rendered ? 'mt-5' : '' }}">{{ $heading }}</h3>
                            @php($rendered = true)

                            <ul class="mt-2 list-inside list-disc space-y-2 text-sm text-slate-700">
                                @foreach ($requirements[$bucket] as $requirement)
                                    <li>
                                        {{ \Illuminate\Support\Str::headline(str_replace(['.', '_'], ' ', $requirement['description'])) }}

                                        @if ($requirement['awaiting_action_from'] === 'stripe')
                                            <span class="ml-1 text-xs text-slate-400">Stripe is reviewing this</span>
                                        @endif

                                        @foreach ($requirement['errors'] as $error)
                                            <span class="mt-1 block font-mono text-xs text-rose-700">{{ $error }}</span>
                                        @endforeach
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @endforeach

                    <p class="mt-4 text-sm text-slate-500">
                        Continue onboarding with Stripe to provide these details.
                    </p>
                @endif
            </x-ui.card>
        @endif
    </div>
</div>
