<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Validación de Recargas</h3>

    @if($recargas->isEmpty())
        <p class="text-center text-gray-500 py-8">No hay recargas pendientes por validar.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left">
                        <th class="py-2 pr-4 font-medium text-gray-600">Cliente</th>
                        <th class="py-2 pr-4 font-medium text-gray-600">Fecha</th>
                        <th class="py-2 pr-4 font-medium text-gray-600">Monto USD</th>
                        <th class="py-2 pr-4 font-medium text-gray-600">Créditos</th>
                        <th class="py-2 pr-4 font-medium text-gray-600">Comprobante</th>
                        <th class="py-2 font-medium text-gray-600">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recargas as $recarga)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-2 pr-4">
                                <div class="text-gray-900">{{ $recarga->cliente->usuario->nombre ?? '—' }}</div>
                                <div class="text-xs text-gray-500">{{ $recarga->cliente->usuario->email ?? '—' }}</div>
                            </td>
                            <td class="py-2 pr-4 text-gray-700 whitespace-nowrap">{{ $recarga->fecha->format('d/m/Y H:i') }}</td>
                            <td class="py-2 pr-4 text-gray-700">{{ number_format($recarga->monto_usd, 2) }}</td>
                            <td class="py-2 pr-4 font-medium text-gray-900">{{ $recarga->creditos_obtenidos }}</td>
                            <td class="py-2 pr-4">
                                @if($recarga->comprobante_url)
                                    <a href="{{ $recarga->comprobante_url }}" target="_blank"
                                        class="text-indigo-600 hover:text-indigo-800 text-sm">Ver comprobante</a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="py-2">
                                <div class="flex items-center gap-3">
                                    @if($esAdmin)
                                        @if($acreditandoId === $recarga->id)
                                            <button type="button" wire:click="toggleAcreditar({{ $recarga->id }})"
                                                class="text-green-600 hover:text-green-800 text-sm">Cancelar</button>
                                        @else
                                            <button type="button" wire:click="toggleAcreditar({{ $recarga->id }})"
                                                class="text-green-600 hover:text-green-800 text-sm">Acreditar</button>
                                        @endif
                                    @endif
                                    @if($rechazandoId === $recarga->id)
                                        <button type="button" wire:click="toggleRechazar({{ $recarga->id }})"
                                            class="text-red-600 hover:text-red-800 text-sm">Cancelar</button>
                                    @else
                                        <button type="button" wire:click="toggleRechazar({{ $recarga->id }})"
                                            class="text-red-600 hover:text-red-800 text-sm">Rechazar</button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        @if($acreditandoId === $recarga->id)
                            <tr class="bg-gray-50">
                                <td colspan="6" class="px-4 py-4">
                                    <form wire:submit="acreditar({{ $recarga->id }})" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                                        <div>
                                            <label class="block text-xs text-gray-600 mb-1">Créditos</label>
                                            <input type="number" min="1" wire:model="creditos"
                                                class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @error('creditos')
                                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-600 mb-1">Motivo</label>
                                            <input type="text" wire:model="motivo"
                                                placeholder="Ej. Depósito bancario Bco. Pichincha"
                                                class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @error('motivo')
                                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <button type="submit"
                                                class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                Acreditar
                                            </button>
                                            <button type="button" wire:click="toggleAcreditar({{ $recarga->id }})"
                                                class="px-4 py-2 bg-gray-200 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-300">
                                                Cancelar
                                            </button>
                                        </div>
                                    </form>
                                    @error('acreditacion')
                                        <p class="text-red-600 text-xs mt-2">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                        @endif

                        @if($rechazandoId === $recarga->id)
                            <tr class="bg-gray-50">
                                <td colspan="6" class="px-4 py-4">
                                    <form wire:submit="rechazar({{ $recarga->id }})" class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                                        <div>
                                            <label class="block text-xs text-gray-600 mb-1">Motivo de rechazo</label>
                                            <input type="text" wire:model="motivoRechazo"
                                                placeholder="Ej. Comprobante ilegible"
                                                class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @error('motivoRechazo')
                                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <button type="submit"
                                                class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                Rechazar
                                            </button>
                                            <button type="button" wire:click="toggleRechazar({{ $recarga->id }})"
                                                class="px-4 py-2 bg-gray-200 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-300">
                                                Cancelar
                                            </button>
                                        </div>
                                    </form>
                                    @error('rechazo')
                                        <p class="text-red-600 text-xs mt-2">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($recargas->hasPages())
        <div class="mt-4">
            {{ $recargas->links() }}
        </div>
    @endif

    @if (session('status'))
        <p class="mt-4 text-sm text-green-600">{{ session('status') }}</p>
    @endif
</div>