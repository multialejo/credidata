<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Log de Trazabilidad</h3>
        <button type="button" wire:click="resetFilters"
            class="px-4 py-2 bg-gray-200 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
            {{ __('Limpiar filtros') }}
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-4">
        <div>
            <label class="block text-xs text-gray-600 mb-1">{{ __('Acción') }}</label>
            <select wire:model.live="accion"
                class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('Todas las acciones') }}</option>
                @foreach($accionesConocidas as $a)
                    <option value="{{ $a }}">{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-600 mb-1">{{ __('Desde') }}</label>
            <input type="date" wire:model.live="fechaDesde"
                class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-xs text-gray-600 mb-1">{{ __('Hasta') }}</label>
            <input type="date" wire:model.live="fechaHasta"
                class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-xs text-gray-600 mb-1">{{ __('Actor') }}</label>
            <input type="text" wire:model.live="actorEmail" placeholder="{{ __('Buscar actor por email') }}"
                class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
    </div>

    @if($logs->isEmpty())
        <p class="text-center text-gray-500 py-8">{{ __('No hay logs que coincidan con los filtros.') }}</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left">
                        <th class="py-2 pr-4 font-medium text-gray-600">{{ __('Fecha') }}</th>
                        <th class="py-2 pr-4 font-medium text-gray-600">{{ __('Acción') }}</th>
                        <th class="py-2 pr-4 font-medium text-gray-600">{{ __('Actor') }}</th>
                        <th class="py-2 pr-4 font-medium text-gray-600">{{ __('IP Origen') }}</th>
                        <th class="py-2 font-medium text-gray-600">{{ __('Detalle') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-2 pr-4 text-gray-700 whitespace-nowrap">
                                {{ $log->fecha->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-2 pr-4">
                                @php
                                    $badgeClass = match (true) {
                                        in_array($log->accion, ['API_KEY_GENERADA', 'API_KEY_REVOCADA', 'AUTH_TOKEN_EMITIDO', 'CREDITOS_ASIGNADOS', 'STAFF_CREADO', 'CLIENTE_REGISTRADO'], true)
                                            => 'bg-indigo-100 text-indigo-800',
                                        $log->accion === 'CONSULTA_CEDULA'
                                            => 'bg-blue-100 text-blue-800',
                                        $log->accion === 'recarga.acreditada'
                                            => 'bg-green-100 text-green-800',
                                        in_array($log->accion, ['recarga.fallida', 'recarga.rechazada'], true)
                                            => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                @endphp
                                <span class="uppercase text-xs font-medium px-2 py-0.5 rounded {{ $badgeClass }}">
                                    {{ $log->accion }}
                                </span>
                            </td>
                            <td class="py-2 pr-4 text-gray-700">
                                @if($log->actor_sistema)
                                    <span class="text-gray-500 italic">{{ __('Sistema') }}</span>
                                @else
                                    {{ $log->actor?->email ?? '—' }}
                                @endif
                            </td>
                            <td class="py-2 pr-4 font-mono text-xs text-gray-700">
                                {{ $log->ip_origen ?? '—' }}
                            </td>
                            <td class="py-2">
                                @if(is_array($log->detalle) && count($log->detalle) > 0)
                                    <pre class="text-xs bg-gray-50 p-2 rounded overflow-x-auto">{{ json_encode($log->detalle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($logs->hasPages())
        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    @endif
</div>
