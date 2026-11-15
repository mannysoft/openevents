<div class="mx-auto max-w-3xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold">
                {{ $organiser?->exists ? 'Edit organiser' : 'New organiser' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                @if ($organiser?->exists)
                    Public page: <a href="{{ route('organisers.show', $organiser) }}" class="underline" target="_blank">{{ route('organisers.show', $organiser) }}</a>
                @else
                    Organisers hold your events, branding and payout details.
                @endif
            </p>
        </div>

        <a href="{{ route('admin.organisers.index') }}" wire:navigate class="text-sm text-slate-600 hover:text-slate-900">Back to organisers</a>
    </div>

    <form wire:submit="save" class="space-y-4">
        <x-ui.card title="Details">
            <div class="space-y-4">
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium">Name</label>
                    <input id="name" type="text" wire:model="name"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                        <input id="email" type="email" wire:model="email"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                        @error('email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="phone" class="mb-1 block text-sm font-medium">Phone</label>
                        <input id="phone" type="text" wire:model="phone"
                               class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                        @error('phone') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="about" class="mb-1 block text-sm font-medium">About</label>
                    <textarea id="about" rows="5" wire:model="about"
                              class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                    @error('about') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="logo" class="mb-1 block text-sm font-medium">Logo</label>

                    <div class="flex items-center gap-4">
                        @if ($logo && $logo->isPreviewable())
                            <img src="{{ $logo->temporaryUrl() }}" alt="Logo preview" class="h-14 w-14 rounded-lg border border-slate-200 object-cover">
                        @elseif ($organiser?->logoUrl())
                            <img src="{{ $organiser->logoUrl() }}" alt="{{ $organiser->name }} logo" class="h-14 w-14 rounded-lg border border-slate-200 object-cover">
                        @endif

                        <input id="logo" type="file" wire:model="logo" accept="image/*"
                               class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white">
                    </div>

                    <p class="mt-1 text-xs text-slate-500">PNG or JPG, up to 2MB.</p>
                    <p wire:loading wire:target="logo" class="mt-1 text-xs text-slate-500">Uploading…</p>
                    @error('logo') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Payments & locale">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="currency_code" class="mb-1 block text-sm font-medium">Currency</label>
                    <select id="currency_code" wire:model="currency_code"
                            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                        @foreach (array_keys(config('events.currencies')) as $code)
                            <option value="{{ $code }}">{{ $code }}</option>
                        @endforeach
                    </select>
                    @error('currency_code') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="timezone" class="mb-1 block text-sm font-medium">Timezone</label>
                    @php
                        $timezones = ['Europe/London', 'Europe/Dublin', 'Europe/Paris', 'Europe/Berlin', 'Europe/Madrid', 'Europe/Rome', 'Europe/Amsterdam', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'America/Toronto', 'Asia/Singapore', 'Asia/Hong_Kong', 'Asia/Tokyo', 'Asia/Dubai', 'Australia/Sydney', 'Pacific/Auckland', 'UTC'];
                        if ($timezone !== '' && ! in_array($timezone, $timezones, true)) {
                            array_unshift($timezones, $timezone);
                        }
                    @endphp
                    <select id="timezone" wire:model="timezone"
                            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                        @foreach ($timezones as $zone)
                            <option value="{{ $zone }}">{{ $zone }}</option>
                        @endforeach
                    </select>
                    @error('timezone') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="platform_fee_percent" class="mb-1 block text-sm font-medium">Platform fee (%)</label>
                    <input id="platform_fee_percent" type="number" step="0.01" min="0" max="100"
                           wire:model="platform_fee_percent"
                           placeholder="{{ config('services.stripe.fee_percent', 5) }}"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    <p class="mt-1 text-xs text-slate-500">Leave blank to use the platform default of {{ config('services.stripe.fee_percent', 5) }}%.</p>
                    @error('platform_fee_percent') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.organisers.index') }}" wire:navigate
               class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                Cancel
            </a>
            <button type="submit"
                    class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                {{ $organiser?->exists ? 'Save changes' : 'Create organiser' }}
            </button>
        </div>
    </form>
</div>
