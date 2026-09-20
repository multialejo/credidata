<div class="space-y-3">
    <div wire:loading.remove wire:target="pay">
        @if($payWithPayphone && $payWithCard)
            @if($soloTarjeta)
                <p class="text-sm text-slate-600">
                    Serás redirigido a Payphone para completar el pago con tarjeta.
                </p>
                <p class="text-xs text-slate-500 font-mono">
                    Referencia: {{ $clientTransactionId }}
                </p>
                <a
                    href="{{ $payWithCard }}"
                    rel="noopener"
                    class="ui-primary-button w-full"
                >
                    Pagar con tarjeta
                </a>
            @else
                <p class="text-sm text-slate-600">
                    Elige cómo quieres pagar. Serás redirigido a Payphone para completar el pago.
                </p>
                <p class="text-xs text-slate-500 font-mono">
                    Referencia: {{ $clientTransactionId }}
                </p>
                <div class="flex flex-col sm:flex-row gap-2">
                    <a
                        href="{{ $payWithPayphone }}"
                        rel="noopener"
                        class="ui-primary-button flex-1"
                    >
                        Pagar con PayPhone
                    </a>
                    <a
                        href="{{ $payWithCard }}"
                        rel="noopener"
                        class="ui-secondary-button flex-1"
                    >
                        Pagar con tarjeta
                    </a>
                </div>
            @endif
        @else
            <button
                type="button"
                wire:click="pay"
                @disabled(! $this->montoValido)
                class="ui-primary-button w-full"
            >
                @if($soloTarjeta)
                    Pagar con tarjeta
                @else
                    Pagar con Payphone
                @endif
            </button>
        @endif
    </div>

    <p wire:loading wire:target="pay" aria-live="polite" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600">
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        Procesando...
    </p>

    @if($errorMessage)
        <p class="mt-3 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700" role="alert">{{ $errorMessage }}</p>
    @endif
</div>