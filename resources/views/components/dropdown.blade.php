@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white'])

@php
$alignmentClasses = match ($align) {
    'left' => 'ltr:origin-top-left rtl:origin-top-right start-0 mt-2',
    'top' => 'origin-top mt-2',
    'top-left' => 'ltr:origin-bottom-left rtl:origin-bottom-right start-0 bottom-full mb-2',
    'top-right' => 'ltr:origin-bottom-right rtl:origin-bottom-left end-0 bottom-full mb-2',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0 mt-2',
};

$width = match ($width) {
    '48' => 'w-48',
    default => $width,
};
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <button
        type="button"
        @click="open = ! open"
        :aria-expanded="open.toString()"
        aria-haspopup="menu"
        class="w-full text-start focus:outline-none focus-visible:ring-2 focus-visible:ring-[#3155d9] focus-visible:ring-offset-2"
    >
        {{ $trigger }}
    </button>

    <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 {{ $width }} rounded-md shadow-lg {{ $alignmentClasses }}"
            style="display: none;"
            @click="open = false">
            <div class="rounded-xl ring-1 ring-black/5 {{ $contentClasses }}" role="menu">
                {{ $content }}
            </div>
    </div>
</div>
