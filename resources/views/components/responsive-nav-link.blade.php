@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-lg px-3 py-2 text-start text-base font-semibold text-white bg-white/10 focus:outline-none focus:ring-2 focus:ring-white transition'
            : 'block w-full rounded-lg px-3 py-2 text-start text-base font-medium text-slate-300 hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
