<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-6">
    <header>
        <h3 class="text-lg font-semibold text-gray-900">Recargar créditos</h3>
        <p class="mt-1 text-sm text-gray-600">
            Elegí el método de pago y el monto en USD que querés cargar a tu cuenta.
        </p>
    </header>

    <div role="radiogroup" aria-label="Método de pago" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        @foreach(['paypal' => 'PayPal', 'payphone' => 'PayPhone', 'transferencia' => 'Transferencia'] as $codigo => $etiqueta)
            @php $disponible = in_array($codigo, $metodosDisponibles, true); @endphp
            <button
                type="button"
                role="radio"
                aria-checked="{{ $metodo === $codigo ? 'true' : 'false' }}"
                @if($disponible) wire:click="selectMetodo('{{ $codigo }}')" @endif
                @class([
                    'flex flex-col items-start gap-1 rounded-lg border p-4 text-left transition',
                    'border-indigo-600 bg-indigo-50 ring-2 ring-indigo-200' => $metodo === $codigo && $disponible,
                    'border-gray-200 bg-white hover:border-gray-300' => $metodo !== $codigo && $disponible,
                    'border-gray-200 bg-gray-50 cursor-not-allowed opacity-60' => ! $disponible,
                ])
                @disabled(! $disponible)
            >
                <span class="font-semibold text-gray-900">{{ $etiqueta }}</span>
                @if(! $disponible)
                    <span class="text-xs text-gray-500">Próximamente</span>
                @elseif($metodo === $codigo)
                    <span class="text-xs text-indigo-700">Seleccionado</span>
                @endif
            </button>
        @endforeach
    </div>

    <div>
        <label for="monto" class="block text-sm font-medium text-gray-700">Monto (USD)</label>
        <input
            id="monto"
            type="number"
            step="0.01"
            min="5"
            wire:model.live="monto"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            placeholder="10.00"
        />
        @error('monto')
            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <div class="border-t border-gray-100 pt-4">
        @if($metodo === 'paypal')
            <livewire:pay-with-paypal :monto="$monto" :key="'paypal-'.$metodo" />
        @endif
    </div>
</div>
