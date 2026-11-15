@props(['event'])

<nav class="mb-6 flex flex-wrap items-center gap-1 rounded-lg border border-slate-200 bg-white p-1 text-sm">
    @php
        $links = [
            ['label' => 'Event', 'route' => 'admin.events.edit', 'params' => [$event->organiser, $event], 'active' => 'admin.events.edit'],
            ['label' => 'Tickets', 'route' => 'admin.tickets.index', 'params' => [$event], 'active' => 'admin.tickets.*'],
            ['label' => 'Orders', 'route' => 'admin.orders.index', 'params' => [$event], 'active' => 'admin.orders.index'],
            ['label' => 'Attendees', 'route' => 'admin.attendees.index', 'params' => [$event], 'active' => 'admin.attendees.*'],
            ['label' => 'Check-in', 'route' => 'admin.checkin.index', 'params' => [$event], 'active' => 'admin.checkin.*'],
            ['label' => 'Discounts', 'route' => 'admin.discounts.index', 'params' => [$event], 'active' => 'admin.discounts.*'],
            ['label' => 'Questions', 'route' => 'admin.questions.index', 'params' => [$event], 'active' => 'admin.questions.*'],
            ['label' => 'Messages', 'route' => 'admin.messages.index', 'params' => [$event], 'active' => 'admin.messages.*'],
        ];
    @endphp

    @foreach ($links as $link)
        <a href="{{ route($link['route'], $link['params']) }}"
           class="rounded-md px-3 py-1.5 {{ request()->routeIs($link['active']) ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
            {{ $link['label'] }}
        </a>
    @endforeach
</nav>
