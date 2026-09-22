@props([
    'maxWidth' => '6xl',
])

<div {{ $attributes->class("ui-page-shell max-w-{$maxWidth}") }}>
    {{ $slot }}
</div>
