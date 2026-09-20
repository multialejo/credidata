<div class="space-y-6">
{{-- Success Notification Banner (Placed at the top for immediate visibility) --}}
    @if(session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-2">
                {{-- Success Icon --}}
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif
    <section class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900">Acreditar transferencia</h3>
        @if($esAdmin)
            <form wire:submit="acreditar" enctype="multipart/form-data" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="clienteEmail" value="Email del cliente" />
                    <x-text-input id="clienteEmail" wire:model="clienteEmail" type="email" class="mt-1 block w-full" />
                    @error('clienteEmail') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-input-label for="montoUsd" value="Monto USD" />
                    <x-text-input id="montoUsd" wire:model="montoUsd" type="number" min="0.01" step="0.01" class="mt-1 block w-full" />
                    <p class="text-xs text-gray-500 mt-1">Créditos calculados: {{ $this->creditosCalculados }}</p>
                    @error('montoUsd') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-input-label for="referenciaBancaria" value="Referencia bancaria" />
                    <x-text-input id="referenciaBancaria" wire:model="referenciaBancaria" class="mt-1 block w-full" />
                    @error('referenciaBancaria') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-input-label for="comprobante" value="Comprobante (JPG, PNG o PDF)" />
                    <input id="comprobante" wire:model="comprobante" type="file" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full text-sm" />
                    @error('comprobante') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="motivo" value="Motivo" />
                    <textarea id="motivo" wire:model="motivo" rows="2" class="mt-1 block w-full border-gray-300 rounded-md"></textarea>
                    @error('motivo') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <x-primary-button wire:loading.attr="disabled">Acreditar transferencia</x-primary-button>
                </div>
            </form>
        @else
            <p class="mt-2 text-sm text-gray-600">Solo el rol admin puede acreditar transferencias.</p>
        @endif
    </section>
</div>
