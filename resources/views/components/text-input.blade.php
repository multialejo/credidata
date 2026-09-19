@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-300 focus:border-[#3155d9] focus:ring-[#3155d9] rounded-lg shadow-sm']) }}>
