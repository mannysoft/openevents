<div>
    <h1 class="text-xl font-semibold">Create your account</h1>
    <p class="mt-1 text-sm text-slate-500">Start selling tickets in minutes.</p>

    <form wire:submit="register" class="mt-6 space-y-4">
        <div>
            <label for="name" class="mb-1 block text-sm font-medium">Name</label>
            <input id="name" type="text" wire:model="name" required autofocus
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
            @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email</label>
            <input id="email" type="email" wire:model="email" required
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
            @error('email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium">Password</label>
            <input id="password" type="password" wire:model="password" required
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
            @error('password') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium">Confirm password</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation" required
                   class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:outline-none">
        </div>

        <button type="submit" class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            Create account
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        Already registered?
        <a href="{{ route('login') }}" class="hover:text-slate-900" wire:navigate>Sign in</a>
    </p>
</div>
