<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Log de Actividad</h3>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-4">
        <input type="text" wire:model.live.debounce.300ms="accion" placeholder="Acción..."
            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
        <input type="text" wire:model.live.debounce.300ms="actor_email" placeholder="Email del actor..."
            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
        <input type="date" wire:model.live="fecha_desde"
            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
        <input type="date" wire:model.live="fecha_hasta"
            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acción</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detalle</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($logs as $log)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $log->fecha->format('d/m/Y H:i:s') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $log->actor_sistema ? 'Sistema' : ($log->actor?->email ?? '-') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $log->accion }}</td>
                        <td class="px-4 py-3 align-top">
                            @if($log->detalle)
                                <details class="group">
                                    <summary class="cursor-pointer text-xs text-indigo-600 font-medium hover:underline list-none flex items-center gap-1">
                                        <span class="transition group-open:rotate-90">▶</span>
                                        Mostrar JSON
                                    </summary>
                                    <pre class="mt-2 text-[11px] font-mono bg-slate-50 border rounded p-2 text-slate-800 overflow-x-auto max-w-xs">{{ json_encode(is_array($log->detalle) ? $log->detalle : json_decode($log->detalle), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </details>
                            @else
                                <span class="text-sm text-gray-900">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No se encontraron logs.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>
