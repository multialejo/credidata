<x-guest-layout>
    <div class="text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 ring-1 ring-amber-100">
            <svg class="h-7 w-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.071 19h13.858c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h1 class="mt-5 text-xl font-bold tracking-tight text-[#14213d]">Has cancelado el pago</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">
            Tu orden fue cancelada y no se realizará ningún cobro. Si completaste el pago, revisa tu saldo o reintenta la recarga.
        </p>
        @if($clientTransactionId)
            <p class="mt-2 text-xs text-slate-500 font-mono">Referencia: {{ $clientTransactionId }}</p>
        @endif
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
</x-guest-layout>
