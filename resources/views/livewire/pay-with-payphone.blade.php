<div class="space-y-3">
    <div wire:loading.remove wire:target="pay">
        @if($payWithPayphone && $payWithCard)
            <p class="text-sm text-gray-600">
                Elegí cómo querés pagar. Serás redirigido a Payphone para completar el pago.
            </p>
            <p class="text-xs text-gray-500 font-mono">
                Referencia: {{ $clientTransactionId }}
            </p>
            <div class="flex flex-col sm:flex-row gap-2">
                <a
                    href="{{ $payWithPayphone }}"
                    rel="noopener"
                    class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Pagar con PayPhone
                </a>
                <a
                    href="{{ $payWithCard }}"
                    rel="noopener"
                    class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Pagar con tarjeta
                </a>
            </div>
        @else
            <button
                type="button"
                wire:click="pay"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
                Pagar con Payphone
            </button>
        @endif
    </div>

    <p wire:loading wire:target="pay" class="text-sm text-gray-600">Procesando...</p>

    @if($errorMessage)
        <p class="mt-2 text-sm text-red-600" role="alert">{{ $errorMessage }}</p>
    @endif
</div>
