<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Validación de Recargas</h3>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Créditos</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comprobante</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($pendientes as $recarga)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $recarga->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $recarga->cliente->usuario->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${{ number_format($recarga->monto_usd, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $recarga->creditos_obtenidos }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $recarga->metodo }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $recarga->fecha->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($recarga->comprobante_url)
                                <a href="{{ $recarga->comprobante_url }}" target="_blank" class="text-indigo-600 hover:text-indigo-900">Ver</a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button wire:click="seleccionarRecarga({{ $recarga->id }})" class="text-indigo-600 hover:text-indigo-900">Acreditar</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">No hay recargas pendientes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $pendientes->links() }}
    </div>

    @if($mostrarFormulario && $recargaSeleccionada)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="cerrarFormulario">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-md shadow-lg rounded-md bg-white" @click.stop>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Acreditar Recarga #{{ $recargaSeleccionada->id }}</h3>

                @if($esAdmin)
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Créditos</label>
                        <input type="number" wire:model.live="creditos" min="1"
                            class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full" />
                    </div>
                @endif

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Motivo</label>
                    <textarea wire:model.live="motivo"
                        class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full"></textarea>
                </div>

                <div class="flex gap-2 justify-end">
                    @if($esAdmin)
                        <button wire:click="acreditar" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Acreditar</button>
                    @endif
                    <button wire:click="rechazar" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Rechazar</button>
                    <button wire:click="cerrarFormulario" class="px-4 py-2 bg-gray-300 rounded-md">Cancelar</button>
                </div>
            </div>
        </div>
    @endif
</div>
