<div>
    <h1 class="text-xl font-semibold">Sign in</h1>
    <p class="mt-1 text-sm text-slate-500">Manage your events, tickets and attendees.</p>

    <form wire:submit="login" class="mt-6 space-y-4">
        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email</label>
            <input id="email" type="email" wire:model="email" required autofocus
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
            @error('email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium">Password</label>
            <input id="password" type="password" wire:model="password" required
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
            @error('password') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" wire:model="remember" class="rounded border-slate-300">
            Remember me
        </label>

        <button type="submit" class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            Sign in
        </button>
    </form>

    <div class="mt-6 flex items-center justify-between text-sm text-slate-500">
        <a href="{{ route('password.request') }}" class="hover:text-slate-900" wire:navigate>Forgot password?</a>
        <a href="{{ route('register') }}" class="hover:text-slate-900" wire:navigate>Create an account</a>
    </div>
</div>
