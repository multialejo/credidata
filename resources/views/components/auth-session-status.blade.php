@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'ui-alert ui-alert--success']) }} role="status">
        {{ $status }}
    </div>
@endif
