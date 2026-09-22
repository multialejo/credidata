<button {{ $attributes->merge(['type' => 'submit', 'class' => 'ui-primary-button']) }}>
    {{ $slot }}
</button>
