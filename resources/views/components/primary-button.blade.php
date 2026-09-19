<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-[#3155d9] border border-transparent rounded-lg font-semibold text-sm text-white tracking-wide hover:bg-[#2745b8] active:bg-[#1e3599] focus:outline-none focus:ring-2 focus:ring-[#3155d9] focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
