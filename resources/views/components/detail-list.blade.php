@props(['items'])

<dl class="divide-y divide-slate-100">
    @foreach($items as $key => $value)
        @php
            $label = is_int($key)
                ? 'Elemento '.($key + 1)
                : ucwords(str_replace(['_', '-'], ' ', (string) $key));
        @endphp
        <div class="grid gap-1 py-3 first:pt-0 last:pb-0 sm:grid-cols-[minmax(10rem,0.75fr)_minmax(0,1.5fr)] sm:gap-4">
            <dt class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500">{{ $label }}</dt>
            <dd class="min-w-0 text-sm text-slate-700">
                @if(is_array($value))
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3">
                        <x-detail-list :items="$value" />
                    </div>
                @elseif(is_bool($value))
                    {{ $value ? 'Sí' : 'No' }}
                @elseif($value === null || $value === '')
                    <span class="text-slate-400">Sin información</span>
                @else
                    <span class="break-words {{ is_string($value) && preg_match('/(^|_)(id|ip|uid|referencia|prefijo)($|_)/i', (string) $key) ? 'font-mono text-xs' : '' }}">{{ $value }}</span>
                @endif
            </dd>
        </div>
    @endforeach
</dl>
