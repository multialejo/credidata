<x-guest-layout>
    @switch($status ?? null)
        @case('completada')
            <div class="text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 ring-1 ring-emerald-100">
                    <svg class="h-7 w-7 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 class="mt-5 text-xl font-bold tracking-tight text-[#14213d]">Recarga acreditada</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Tu recarga por
                    <span class="font-semibold text-emerald-600">
                        {{ number_format((int) ($recarga?->creditos_obtenidos ?? $intencion?->creditos_estimados ?? 0), 0) }}
                    </span>
                    créditos fue procesada con éxito.
                </p>
                <p class="mt-2 text-xs text-slate-500 font-mono">
                    Referencia: {{ $recarga?->referencia_externa ?? $intencion?->ctid }}
                </p>
                <a href="{{ route('dashboard') }}" class="ui-primary-button mt-8 w-full">
                    Volver a Inicio
                </a>
            </div>
        @break

        @case('pendiente')
            <div class="text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 ring-1 ring-amber-100">
                    <svg class="h-7 w-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h1 class="mt-5 text-xl font-bold tracking-tight text-[#14213d]">Pendiente</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Tu orden <span class="font-mono text-slate-700">{{ $intencion?->ctid }}</span>
                    está pendiente de acreditación.
                </p>
                @auth
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Si acabas de aprobar el pago en Payphone, recarga esta página en unos segundos.
                    </p>
                @else
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Inicia sesión con la cuenta que usaste para la recarga para acreditar tu saldo.
                    </p>
                    <a href="{{ route('login') }}" class="ui-primary-button mt-6 w-full">
                        Ir al login
                    </a>
                @endauth
            </div>
        @break

        @case('fallida')
            <div class="text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-rose-50 ring-1 ring-rose-100">
                    <svg class="h-7 w-7 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1 class="mt-5 text-xl font-bold tracking-tight text-[#14213d]">Pago no completado</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Tu orden no pudo ser procesada por Payphone. Puedes reintentar la recarga.
                </p>
                <div class="mt-8 space-y-3">
                    @auth
                        <a href="{{ route('dashboard.recargas') }}" class="ui-primary-button w-full">
                            Reintentar recarga
                        </a>
                    @endauth
                    <a href="{{ route('dashboard') }}" class="ui-secondary-button w-full">
                        Volver a Inicio
                    </a>
                </div>
            </div>
        @break

        @default
            <div class="text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 ring-1 ring-slate-200">
                    <svg class="h-7 w-7 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h1 class="mt-5 text-xl font-bold tracking-tight text-[#14213d]">Orden no encontrada</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    No pudimos encontrar la orden asociada a esta URL.
                </p>
                <a href="{{ route('dashboard') }}" class="ui-primary-button mt-8 w-full">
                    Volver a Inicio
                </a>
            </div>
    @endswitch
</x-guest-layout>
