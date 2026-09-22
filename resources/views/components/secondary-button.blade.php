<button {{ $attributes->merge(['type' => 'button', 'class' => 'ui-secondary-button']) }}>
    {{ $slot }}
</button>
