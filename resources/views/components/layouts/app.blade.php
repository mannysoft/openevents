<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50 text-slate-900 antialiased">
    <div class="flex min-h-screen">
        <aside class="hidden w-60 shrink-0 border-r border-slate-200 bg-white lg:block">
            <div class="flex h-16 items-center border-b border-slate-200 px-5">
                <a href="{{ route('admin.dashboard') }}" class="text-base font-semibold tracking-tight">{{ config('app.name') }}</a>
            </div>
            <nav class="space-y-1 p-3 text-sm">
                <a href="{{ route('admin.dashboard') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.dashboard') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Dashboard</a>
                <a href="{{ route('admin.organisers.index') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.organisers.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Organisers</a>
                <a href="{{ route('admin.payouts.index') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('admin.payouts.*') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Payouts</a>
            </nav>
            <div class="mt-auto border-t border-slate-200 p-3">
                <a href="{{ route('events.index') }}" class="block rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-100">View public site</a>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 lg:px-8">
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold lg:hidden">{{ config('app.name') }}</a>
                    <h1 class="text-lg font-semibold">{{ $heading ?? ($title ?? 'Dashboard') }}</h1>
                </div>
                <div class="flex items-center gap-4 text-sm">
                    <span class="hidden text-slate-500 sm:inline">{{ auth()->user()?->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-md border border-slate-300 px-3 py-1.5 text-slate-700 hover:bg-slate-100">Sign out</button>
                    </form>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        {{ session('error') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
