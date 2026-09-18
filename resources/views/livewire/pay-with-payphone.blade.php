<div class="space-y-3">
    <div wire:loading.remove wire:target="pay">
        @if($payWithPayphone && $payWithCard)
            <p class="text-sm text-slate-600">
                Elegí cómo querés pagar. Serás redirigido a Payphone para completar el pago.
            </p>
            <p class="text-xs text-slate-500 font-mono">
                Referencia: {{ $clientTransactionId }}
            </p>
            <div class="flex flex-col sm:flex-row gap-2">
                <a
                    href="{{ $payWithPayphone }}"
                    rel="noopener"
                    class="ui-primary-button"
                >
                    Pagar con PayPhone
                </a>
                <a
                    href="{{ $payWithCard }}"
                    rel="noopener"
                    class="ui-secondary-button"
                >
                    Pagar con tarjeta
                </a>
            </div>
        @else
            <button
                type="button"
                wire:click="pay"
                class="ui-primary-button"
            >
                Pagar con Payphone
            </button>
        @endif
    </div>

    <p wire:loading wire:target="pay" class="text-sm font-medium text-slate-600">Procesando...</p>

    @if($errorMessage)
        <p class="mt-3 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700" role="alert">{{ $errorMessage }}</p>
    @endif
</div>
