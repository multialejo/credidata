@props(['label' => 'Mostrar contraseña'])

<div x-data="{ visible: false }" class="ui-password-field">
    <x-text-input
        {{ $attributes->merge(['class' => 'mt-1 pr-12']) }}
        x-bind:type="visible ? 'text' : 'password'"
    />

    <button
        type="button"
        x-on:click="visible = !visible"
        x-bind:aria-label="visible ? 'Ocultar contraseña' : '{{ $label }}'"
        x-bind:title="visible ? 'Ocultar contraseña' : '{{ $label }}'"
        x-bind:aria-pressed="visible.toString()"
        class="ui-password-field__toggle"
    >
        <svg x-show="!visible" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z" />
            <circle cx="12" cy="12" r="2.5" />
        </svg>
        <svg x-cloak x-show="visible" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18M10.58 10.59a2 2 0 0 0 2.83 2.83M9.88 5.13A10.9 10.9 0 0 1 12 4.92c6.25 0 9.75 7.08 9.75 7.08a17 17 0 0 1-3.18 3.9M6.23 6.24C3.67 8.07 2.25 12 2.25 12s3.5 7.08 9.75 7.08a10 10 0 0 0 3.38-.59" />
        </svg>
    </button>
</div>
