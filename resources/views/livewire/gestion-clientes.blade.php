<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Gestión de Clientes</h3>

    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="busqueda" placeholder="Buscar por nombre, email o prefijo API Key..."
            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full" />
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saldo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">API Key</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Consultas Hoy</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Consultas</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($clientes as $cliente)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $cliente->usuario->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($cliente->saldo_creditos, 0) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $cliente->usuario->estado }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $cliente->api_key_prefijo ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $cliente->consultas_count ?? 0 }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $cliente->consultas_count ?? 0 }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button wire:click="verDetalle({{ $cliente->id }})" class="text-indigo-600 hover:text-indigo-900">Detalle</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No se encontraron clientes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $clientes->links() }}
    </div>

    @if($detalleAbierto && $clienteSeleccionado)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="cerrarDetalle">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white" @click.stop>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Detalle del Cliente</h3>
                <p class="text-sm text-gray-700 mb-2"><strong>Email:</strong> {{ $clienteSeleccionado->usuario->email }}</p>
                <p class="text-sm text-gray-700 mb-2"><strong>Saldo:</strong> {{ number_format($clienteSeleccionado->saldo_creditos, 0) }} créditos</p>

                <h4 class="text-md font-semibold text-gray-700 mt-4 mb-2">Últimas consultas</h4>
                @forelse($clienteSeleccionado->consultas as $consulta)
                    <p class="text-sm text-gray-600">{{ $consulta->tipo }} - {{ $consulta->identificador }} ({{ $consulta->fecha->format('d/m/Y H:i') }})</p>
                @empty
                    <p class="text-sm text-gray-500">Sin consultas.</p>
                @endforelse

                <h4 class="text-md font-semibold text-gray-700 mt-4 mb-2">Últimas recargas</h4>
                @forelse($clienteSeleccionado->recargas as $recarga)
                    <p class="text-sm text-gray-600">{{ $recarga->metodo }} - {{ $recarga->creditos_obtenidos }} créditos ({{ $recarga->estado?->value }}) - {{ $recarga->fecha->format('d/m/Y H:i') }}</p>
                @empty
                    <p class="text-sm text-gray-500">Sin recargas.</p>
                @endforelse

                <div class="mt-4">
                    <button wire:click="cerrarDetalle" class="px-4 py-2 bg-gray-300 rounded-md text-sm">Cerrar</button>
                </div>
            </div>
        </div>
    @endif
</div>
