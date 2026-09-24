@props([
    'variant' => 'info',
])

@if ($variant === 'success')
    <div
        {{ $attributes->class("ui-alert ui-alert--{$variant} flex items-start gap-3") }}
        x-data="{ visible: true }"
        x-init="setTimeout(() => visible = false, 5000)"
        x-show="visible"
        x-transition.opacity.duration.300ms
        role="status"
        aria-live="polite"
    >
        <div class="min-w-0 flex-1">{{ $slot }}</div>
        <button
            type="button"
            class="shrink-0 rounded-md p-1 text-emerald-800 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2"
            aria-label="Cerrar mensaje"
            x-on:click="visible = false"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
@else
    <div {{ $attributes->class("ui-alert ui-alert--{$variant}") }} role="status">
        {{ $slot }}
    </div>
@endif
