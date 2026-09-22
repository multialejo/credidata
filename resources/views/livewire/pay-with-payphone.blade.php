<div class="space-y-3">
    <div wire:loading.remove wire:target="pay">
        <button
            type="button"
            wire:click="pay"
            wire:loading.attr="disabled"
            wire:target="pay"
            @disabled(! $this->montoValido)
            class="ui-primary-button w-full"
        >
            @if($soloTarjeta)
                Pagar con tarjeta
            @else
                Pagar con Payphone
            @endif
        </button>
    </div>

    <p wire:loading wire:target="pay" aria-live="polite" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600">
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        Procesando...
    </p>

    @error('monto')
        <p class="ui-error" role="alert">{{ $message }}</p>
    @enderror

    @if($errorMessage)
        <p class="ui-alert ui-alert--danger mt-3" role="alert">{{ $errorMessage }}</p>
    @endif
</div>
