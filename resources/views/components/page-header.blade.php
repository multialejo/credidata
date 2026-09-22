@props([
    'eyebrow' => null,
    'title',
    'description' => null,
])

<header {{ $attributes->class('ui-page-header') }}>
    @if($eyebrow)
        <p class="ui-eyebrow">{{ $eyebrow }}</p>
    @endif
    <h1 class="ui-page-title">{{ $title }}</h1>
    @if($description)
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ $description }}</p>
    @endif
</header>
