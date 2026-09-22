<button {{ $attributes->merge(['type' => 'submit', 'class' => 'ui-danger-button']) }}>
    {{ $slot }}
</button>
