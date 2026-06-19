<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Historial de Consultas</h3>
        <button wire:click="exportarCsv"
            class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
            Exportar CSV
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-4">
        <div>
            <label class="block text-xs text-gray-600 mb-1">Desde</label>
            <input type="date" wire:model.live="filtroFechaDesde"
                class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-xs text-gray-600 mb-1">Hasta</label>
            <input type="date" wire:model.live="filtroFechaHasta"
                class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-xs text-gray-600 mb-1">Tipo</label>
            <select wire:model.live="filtroTipo"
                class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Todos</option>
                <option value="cedula">Cédula</option>
                <option value="ruc">RUC</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-600 mb-1">Resultado</label>
            <select wire:model.live="filtroResultado"
                class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Todos</option>
                <option value="exito">Exitosas</option>
                <option value="fallo">Fallidas</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 text-left">
                    <th class="py-2 pr-4 font-medium text-gray-600">Fecha</th>
                    <th class="py-2 pr-4 font-medium text-gray-600">Tipo</th>
                    <th class="py-2 pr-4 font-medium text-gray-600">Identificador</th>
                    <th class="py-2 pr-4 font-medium text-gray-600">Créditos</th>
                    <th class="py-2 font-medium text-gray-600">Resultado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($consultas as $c)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-2 pr-4 text-gray-700 whitespace-nowrap">
                            {{ $c->fecha->format('d/m/Y H:i') }}
                        </td>
                        <td class="py-2 pr-4">
                            <span class="uppercase text-xs font-medium bg-gray-100 px-2 py-0.5 rounded">{{ $c->tipo }}</span>
                        </td>
                        <td class="py-2 pr-4 font-mono text-sm">{{ $c->identificador }}</td>
                        <td class="py-2 pr-4 font-medium text-red-600">-{{ $c->creditos_gastados }}</td>
                        <td class="py-2">
                            @if($c->exitosa)
                                <span class="text-green-600 font-medium text-xs">Éxito</span>
                            @else
                                <span class="text-red-600 font-medium text-xs">Fallida</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-500">
                            No hay consultas registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($consultas->hasPages())
        <div class="mt-4">
            {{ $consultas->links() }}
        </div>
    @endif
</div>
