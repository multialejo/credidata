<div class="space-y-3">
    <button
        type="button"
        wire:click="pay"
        wire:loading.attr="disabled"
        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
    >
        <span wire:loading.remove wire:target="pay">Pagar con PayPal</span>
        <span wire:loading wire:target="pay">Procesando...</span>
    </button>

    @if($errorMessage)
        <p class="mt-2 text-sm text-red-600" role="alert">{{ $errorMessage }}</p>
    @endif
</div>
