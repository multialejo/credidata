@props(['active', 'icon' => null])

@php
$classes = ($active ?? false)
            ? 'flex items-center gap-3 w-full rounded-lg px-3 py-2.5 text-start text-base font-semibold text-[#14213d] bg-white focus:outline-none focus:ring-2 focus:ring-white transition'
            : 'flex items-center gap-3 w-full rounded-lg px-3 py-2.5 text-start text-base font-medium text-slate-300 hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        <x-dynamic-component :component="'icons.'.$icon" class="h-5 w-5 shrink-0" />
    @endif
    {{ $slot }}
</a>
