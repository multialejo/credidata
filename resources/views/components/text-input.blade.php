@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'ui-input block w-full']) }}>
