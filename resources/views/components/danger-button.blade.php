<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-[#ff6b6b] border border-transparent rounded-lg font-semibold text-sm text-white tracking-wide hover:bg-[#e85d5d] active:bg-[#d94f4f] focus:outline-none focus:ring-2 focus:ring-[#ff6b6b] focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
