<div class="space-y-3">
    <button
        type="button"
        wire:click="pay"
        wire:loading.attr="disabled"
        class="ui-primary-button"
    >
        <span wire:loading.remove wire:target="pay">Continuar con PayPal</span>
        <span wire:loading wire:target="pay">Procesando...</span>
    </button>

    @if($errorMessage)
        <p class="mt-3 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700" role="alert">{{ $errorMessage }}</p>
    @endif
</div>
