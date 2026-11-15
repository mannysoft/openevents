@props(['title' => null])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-5 shadow-sm']) }}>
    @if ($title)
        <h2 class="mb-4 text-base font-semibold">{{ $title }}</h2>
    @endif

    {{ $slot }}
</section>
