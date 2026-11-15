<div>
    <h1 class="text-xl font-semibold">Reset your password</h1>
    <p class="mt-1 text-sm text-slate-500">We will email you a link to choose a new password.</p>

    @if ($sent)
        <div class="mt-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            Check your inbox for the reset link.
        </div>
    @else
        <form wire:submit="send" class="mt-6 space-y-4">
            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                <input id="email" type="email" wire:model="email" required autofocus
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
                @error('email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                Email reset link
            </button>
        </form>
    @endif

    <p class="mt-6 text-center text-sm text-slate-500">
        <a href="{{ route('login') }}" class="hover:text-slate-900" wire:navigate>Back to sign in</a>
    </p>
</div>
