<div class="mx-auto max-w-4xl">
    @if ($event?->exists)
        <x-admin.event-nav :event="$event" />
    @endif

    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm text-slate-500">
                <a href="{{ route('admin.events.index', $organiser) }}" wire:navigate class="hover:text-slate-900">{{ $organiser->name }}</a>
                <span class="mx-1">/</span>
                <span>{{ $event?->exists ? 'Edit event' : 'New event' }}</span>
            </p>
            <h1 class="mt-1 flex items-center gap-3 text-xl font-semibold">
                {{ $event?->exists ? $event->title : 'Create an event' }}
                @if ($event?->exists)
                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $event->isLive() ? 'bg-emerald-100 text-emerald-800' : ($event->status === \App\Enums\EventStatus::Ended ? 'bg-slate-200 text-slate-700' : 'bg-amber-100 text-amber-800') }}">
                        {{ $event->status->label() }}
                    </span>
                @endif
            </h1>
        </div>

        @if ($event?->exists && $event->isLive() && $event->is_public)
            <a href="{{ route('events.show', $event) }}" target="_blank"
               class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                View public page
            </a>
        @endif
    </div>

    @if (! $organiser->stripeReady())
        <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-medium">Stripe is not connected for {{ $organiser->name }}.</p>
            <p class="mt-1">
                This event can still be published and sell tickets, but payments will settle in the
                platform Stripe account until onboarding is complete.
                <a href="{{ route('admin.organisers.stripe', $organiser) }}" wire:navigate class="font-medium underline">Open Stripe settings</a>
            </p>
        </div>
    @endif

    <form wire:submit="save" class="space-y-4">
        <x-ui.card title="Event details">
            <div class="space-y-4">
                <div>
                    <label for="title" class="mb-1 block text-sm font-medium">Title</label>
                    <input id="title" type="text" wire:model="title"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    @error('title') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="description" class="mb-1 block text-sm font-medium">Description</label>
                    <textarea id="description" rows="6" wire:model="description"
                              class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none"></textarea>
                    @error('description') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="cover_image" class="mb-1 block text-sm font-medium">Cover image</label>

                    <div class="flex items-center gap-4">
                        @if ($cover_image && $cover_image->isPreviewable())
                            <img src="{{ $cover_image->temporaryUrl() }}" alt="Cover preview" class="h-20 w-32 rounded-lg border border-slate-200 object-cover">
                        @elseif ($event?->coverImageUrl())
                            <img src="{{ $event->coverImageUrl() }}" alt="{{ $event->title }} cover" class="h-20 w-32 rounded-lg border border-slate-200 object-cover">
                        @endif

                        <input id="cover_image" type="file" wire:model="cover_image" accept="image/*"
                               class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white">
                    </div>

                    <p class="mt-1 text-xs text-slate-500">PNG or JPG, up to 4MB.</p>
                    <p wire:loading wire:target="cover_image" class="mt-1 text-xs text-slate-500">Uploading…</p>
                    @error('cover_image') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Schedule">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="start_date" class="mb-1 block text-sm font-medium">Starts</label>
                    <input id="start_date" type="datetime-local" wire:model="start_date"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    @error('start_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="end_date" class="mb-1 block text-sm font-medium">Ends</label>
                    <input id="end_date" type="datetime-local" wire:model="end_date"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    @error('end_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
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
                    <p class="mt-1 text-xs text-slate-500">Dates above are entered in this timezone.</p>
                    @error('timezone') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Location">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="venue_name" class="mb-1 block text-sm font-medium">Venue name</label>
                    <input id="venue_name" type="text" wire:model="venue_name"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    @error('venue_name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="address1" class="mb-1 block text-sm font-medium">Address</label>
                    <input id="address1" type="text" wire:model="address1"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    @error('address1') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="city" class="mb-1 block text-sm font-medium">City</label>
                    <input id="city" type="text" wire:model="city"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    @error('city') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="state" class="mb-1 block text-sm font-medium">State / region</label>
                    <input id="state" type="text" wire:model="state"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    @error('state') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="postal_code" class="mb-1 block text-sm font-medium">Postcode</label>
                    <input id="postal_code" type="text" wire:model="postal_code"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    @error('postal_code') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="country_code" class="mb-1 block text-sm font-medium">Country code</label>
                    <input id="country_code" type="text" maxlength="2" wire:model="country_code"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm uppercase focus:border-slate-900 focus:outline-none">
                    <p class="mt-1 text-xs text-slate-500">Two-letter ISO code, e.g. GB.</p>
                    @error('country_code') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Visibility">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="max_attendees" class="mb-1 block text-sm font-medium">Maximum attendees</label>
                    <input id="max_attendees" type="number" min="1" wire:model="max_attendees"
                           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                    <p class="mt-1 text-xs text-slate-500">Leave blank for unlimited capacity.</p>
                    @error('max_attendees') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-start pt-6">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="is_public" class="h-4 w-4 rounded border-slate-300">
                        Show this event on the public site
                    </label>
                </div>
            </div>
        </x-ui.card>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <button type="submit"
                        class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                    {{ $event?->exists ? 'Save changes' : 'Create event' }}
                </button>

                @if ($event?->exists)
                    @if ($event->isLive())
                        <button type="button" wire:click="unpublish" wire:loading.attr="disabled"
                                wire:confirm="Unpublish this event and return it to draft?"
                                class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 disabled:opacity-50">
                            Unpublish
                        </button>
                    @else
                        <button type="button" wire:click="publish" wire:loading.attr="disabled"
                                class="rounded-md border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-800 hover:bg-emerald-100 disabled:opacity-50">
                            Publish
                        </button>
                    @endif
                @endif
            </div>

            @if ($event?->exists)
                <button type="button" wire:click="delete"
                        wire:confirm="Delete this event? Attendees will no longer be able to access it."
                        class="rounded-md border border-rose-200 px-4 py-2 text-sm text-rose-700 hover:bg-rose-50">
                    Delete event
                </button>
            @endif
        </div>
    </form>
</div>
