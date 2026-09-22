@props([
    'variant' => 'info',
])

<div {{ $attributes->class("ui-alert ui-alert--{$variant}") }} role="status">
    {{ $slot }}
</div>
