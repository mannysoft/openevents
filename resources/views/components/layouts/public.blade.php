<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50 text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
            <a href="{{ route('events.index') }}" class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</a>
            <nav class="flex items-center gap-4 text-sm">
                <a href="{{ route('events.index') }}" class="text-slate-600 hover:text-slate-900">Events</a>
                @auth
                    <a href="{{ route('admin.dashboard') }}" class="rounded-md bg-slate-900 px-3 py-2 text-white hover:bg-slate-700">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-slate-600 hover:text-slate-900">Sign in</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-slate-900 px-3 py-2 text-white hover:bg-slate-700">Sell tickets</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-6xl px-4 py-10 text-sm text-slate-500">
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </footer>
</body>
</html>
