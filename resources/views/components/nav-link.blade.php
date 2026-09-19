@props(['active', 'icon' => null])

@php
$classes = ($active ?? false)
            ? 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-base font-semibold text-[#14213d] bg-white transition focus:outline-none focus:ring-2 focus:ring-white'
            : 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-base font-medium text-slate-300 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        <x-dynamic-component :component="'icons.'.$icon" class="h-5 w-5 shrink-0" />
    @endif
    {{ $slot }}
</a>
