<div class="space-y-6">
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

    <section class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Pagos de pasarela pendientes</h3>
        @if($recargas->isEmpty())
            <p class="text-center text-gray-500 py-6">No hay pagos pendientes.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead><tr class="border-b text-left"><th class="py-2 pr-4">Cliente</th><th class="py-2 pr-4">Método</th><th class="py-2 pr-4">Fecha</th><th class="py-2">Acciones</th></tr></thead>
                    <tbody>
                        @foreach($recargas as $recarga)
                            <tr class="border-b border-gray-100">
                                <td class="py-3 pr-4">{{ $recarga->cliente?->usuario?->email ?? '—' }}</td>
                                <td class="py-3 pr-4 uppercase">{{ $recarga->metodo }}</td>
                                <td class="py-3 pr-4">{{ $recarga->fecha?->format('d/m/Y H:i') }}</td>
                                <td class="py-3">
                                    @if($rechazandoId === $recarga->id)
                                        <form wire:submit="rechazar({{ $recarga->id }})" class="flex gap-2 items-end">
                                            <input wire:model="motivoRechazo" placeholder="Motivo (mín. 10 caracteres)" class="border-gray-300 rounded-md text-sm" />
                                            <button class="text-red-600 text-sm" type="submit">Confirmar</button>
                                        </form>
                                        @error('motivoRechazo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                                    @else
                                        <button wire:click="toggleRechazar({{ $recarga->id }})" class="text-red-600 text-sm">Rechazar</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($recargas->hasPages()) {{ $recargas->links() }} @endif
        @endif
    </section>
    @if(session('status')) <p class="text-sm text-green-600">{{ session('status') }}</p> @endif
</div>
