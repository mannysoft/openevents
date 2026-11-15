<div class="space-y-6">
    <x-admin.event-nav :event="$event" />

    <div>
        <h2 class="text-xl font-semibold">Check-in</h2>
        <p class="text-sm text-slate-500">{{ $event->title }}</p>
    </div>

    <div class="grid grid-cols-3 gap-3">
        <x-ui.card>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($counters['total']) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Checked in</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ number_format($counters['checked_in']) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Remaining</p>
            <p class="mt-1 text-2xl font-semibold text-slate-700">{{ number_format($counters['remaining']) }}</p>
        </x-ui.card>
    </div>

    <div x-data="{ flash: false }"
         x-on:checkin.window="flash = true; setTimeout(() => flash = false, 1500)"
         :class="flash ? 'rounded-xl ring-4 ring-emerald-400/70' : 'rounded-xl'"
         class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Manual entry">
            <form wire:submit="checkIn" class="flex flex-col gap-3 sm:flex-row sm:items-start">
                <div class="flex-1">
                    <label for="checkin-reference" class="sr-only">Ticket reference</label>
                    <input id="checkin-reference" type="text" wire:model="reference" autofocus autocomplete="off"
                           placeholder="TKT-XXXXXXXXXX"
                           class="w-full rounded-md border border-slate-300 px-4 py-3 font-mono text-lg uppercase tracking-wider focus:border-slate-400 focus:outline-none" />
                    @error('reference')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="rounded-md bg-slate-900 px-6 py-3 text-base font-semibold text-white hover:bg-slate-800">
                    Check in
                </button>
            </form>

            @if ($successMessage)
                <div class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800" role="status">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Checked in</p>
                    <p class="text-lg font-semibold">{{ $successMessage }}</p>
                </div>
            @endif

            @if ($errorMessage)
                <div class="mt-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800" role="alert">
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-600">Not checked in</p>
                    <p class="text-base font-medium">{{ $errorMessage }}</p>
                </div>
            @endif

            <p class="mt-4 text-xs text-slate-500">
                Type a reference and press enter, or scan a QR code with the camera.
            </p>
        </x-ui.card>

        <x-ui.card title="Camera scanner">
            <div id="checkin-qr-reader" wire:ignore
                 class="flex min-h-[240px] items-center justify-center overflow-hidden rounded-lg bg-slate-100 text-sm text-slate-400">
                Camera preview
            </div>

            <p id="checkin-scanner-status" wire:ignore class="mt-3 text-sm text-slate-600">
                Camera idle. Press start to scan with this device.
            </p>

            <div class="mt-3 flex gap-2">
                <button type="button" onclick="window.startCheckinScanner()"
                        class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    Start camera
                </button>
                <button type="button" onclick="window.stopCheckinScanner()"
                        class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                    Stop camera
                </button>
            </div>

            <p class="mt-3 text-xs text-slate-500">
                Camera scanning needs HTTPS or localhost. Scans are ignored if the same code is read twice within 3 seconds.
            </p>
        </x-ui.card>
    </div>

    <x-ui.card title="Latest check-ins">
        @if ($recent->isEmpty())
            <p class="py-8 text-center text-sm text-slate-500">Nobody has been checked in yet.</p>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($recent as $attendee)
                    <li wire:key="recent-{{ $attendee->id }}" class="flex items-center justify-between gap-3 py-2 text-sm">
                        <div class="min-w-0">
                            <p class="truncate font-medium">{{ $attendee->name() ?: 'Unnamed attendee' }}</p>
                            <p class="truncate text-xs text-slate-500">
                                <span class="font-mono">{{ $attendee->reference }}</span>
                                @if ($attendee->ticket)
                                    &middot; {{ $attendee->ticket->title }}
                                @endif
                            </p>
                        </div>
                        <span class="shrink-0 text-xs text-slate-500">
                            {{ $attendee->checked_in_at?->format('H:i:s') }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>
</div>

@script
<script>
    (function () {
        const existing = window.__checkinScanner;

        const state = existing || {
            component: null,
            scanner: null,
            running: false,
            starting: false,
            lastCode: null,
            lastAt: 0,
            loading: null,
        };

        state.component = $wire;
        window.__checkinScanner = state;

        const setStatus = (text) => {
            const el = document.getElementById('checkin-scanner-status');

            if (el) {
                el.textContent = text;
            }
        };

        const loadLibrary = () => {
            if (window.Html5Qrcode) {
                return Promise.resolve();
            }

            if (state.loading) {
                return state.loading;
            }

            state.loading = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/html5-qrcode';
                script.async = true;
                script.onload = () => resolve();
                script.onerror = () => {
                    state.loading = null;
                    reject(new Error('html5-qrcode failed to load'));
                };
                document.head.appendChild(script);
            });

            return state.loading;
        };

        window.startCheckinScanner = async () => {
            if (state.running || state.starting) {
                return;
            }

            state.starting = true;
            setStatus('Loading camera…');

            try {
                await loadLibrary();
            } catch (error) {
                state.starting = false;
                setStatus('Could not load the scanner library. Check your connection and try again.');

                return;
            }

            const container = document.getElementById('checkin-qr-reader');

            if (container) {
                container.innerHTML = '';
            }

            try {
                state.scanner = new window.Html5Qrcode('checkin-qr-reader', { verbose: false });

                await state.scanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 240, height: 240 } },
                    (decodedText) => {
                        const now = Date.now();

                        if (decodedText === state.lastCode && now - state.lastAt < 3000) {
                            return;
                        }

                        state.lastCode = decodedText;
                        state.lastAt = now;
                        state.component.dispatch('barcode-scanned', { reference: decodedText });
                    },
                    () => {},
                );

                state.running = true;
                setStatus('Scanning… point the camera at a ticket QR code.');
            } catch (error) {
                setStatus('Could not start the camera: ' + (error && error.message ? error.message : 'permission denied'));
            } finally {
                state.starting = false;
            }
        };

        window.stopCheckinScanner = async () => {
            if (!state.scanner || !state.running) {
                return;
            }

            try {
                await state.scanner.stop();
            } catch (error) {
                // Camera already stopped.
            }

            state.running = false;
            setStatus('Camera stopped.');
        };

        if (!existing) {
            document.addEventListener('livewire:navigating', () => {
                if (window.__checkinScanner) {
                    window.stopCheckinScanner();
                }
            });
        }
    })();
</script>
@endscript
