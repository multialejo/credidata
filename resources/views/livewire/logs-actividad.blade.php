<x-page-shell max-width="7xl">
    <x-page-header eyebrow="Administración" title="Registro de actividad" description="Trazabilidad de acciones realizadas por usuarios y procesos del sistema." />
<x-data-table title="Actividad registrada" description="Filtra eventos por acción, fecha o actor.">
    <x-slot:actions>
        <button type="button" wire:click="resetFilters" class="ui-secondary-button">Limpiar filtros</button>
    </x-slot:actions>

    <x-slot:filters>
        <div><label class="ui-label mb-1 text-xs" for="log-accion">Acción</label><select id="log-accion" wire:model.live="accion" class="ui-input w-full"><option value="">Todas las acciones</option>@foreach($accionesConocidas as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach</select></div>
        <div><label class="ui-label mb-1 text-xs" for="log-desde">Desde</label><input id="log-desde" type="date" wire:model.live="fechaDesde" class="ui-input w-full"></div>
        <div><label class="ui-label mb-1 text-xs" for="log-hasta">Hasta</label><input id="log-hasta" type="date" wire:model.live="fechaHasta" class="ui-input w-full"></div>
        <div><label class="ui-label mb-1 text-xs" for="log-actor">Actor</label><input id="log-actor" type="search" wire:model.live.debounce.300ms="actorEmail" placeholder="Buscar por correo" class="ui-input w-full"></div>
    </x-slot:filters>

    <thead><tr><th scope="col">Fecha</th><th scope="col">Acción</th><th scope="col">Actor</th><th scope="col">IP de origen</th><th scope="col">Detalle</th></tr></thead>
    <tbody>
        @forelse($logs as $log)
            @php
                $badgeClass = match (true) {
                    in_array($log->accion, ['API_KEY_GENERADA', 'API_KEY_REVOCADA', 'AUTH_TOKEN_EMITIDO', 'CREDITOS_ASIGNADOS', 'STAFF_CREADO', 'CLIENTE_REGISTRADO'], true) => 'bg-blue-100 text-blue-800',
                    $log->accion === 'CONSULTA_CEDULA' => 'bg-sky-100 text-sky-800',
                    $log->accion === 'recarga.acreditada' => 'bg-emerald-100 text-emerald-800',
                    in_array($log->accion, ['recarga.fallida', 'recarga.rechazada'], true) => 'bg-rose-100 text-rose-800',
                    default => 'bg-slate-100 text-slate-700',
                };
            @endphp
            <tr>
                <td class="whitespace-nowrap">{{ $log->fecha->format('d/m/Y H:i') }}</td>
                <td><span class="ui-table-badge {{ $badgeClass }} uppercase">{{ $log->accion }}</span></td>
                <td>@if($log->actor_sistema)<span class="italic text-slate-500">Sistema</span>@else{{ $log->actor?->email ?? '—' }}@endif</td>
                <td class="font-mono text-xs">{{ $log->ip_origen ?? '—' }}</td>
                <td>
                    @if(is_array($log->detalle) && count($log->detalle) > 0)
                        <div x-data="{ open: false }" class="text-xs">
                            <button @click="open = !open" type="button" class="font-semibold text-[#3155d9] underline-offset-2 hover:underline focus:outline-none focus:ring-2 focus:ring-[#3155d9] focus:ring-offset-2">
                                <span x-text="open ? 'Ocultar detalle' : 'Ver detalle'"></span>
                            </button>
                             <pre x-show="open" class="mt-2 max-w-lg overflow-x-auto rounded-lg border border-slate-200 bg-slate-50 p-3 font-mono text-xs text-slate-700">{{ json_encode($log->detalle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @else
                        <span class="text-slate-400">—</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="ui-data-table__empty">No hay registros que coincidan con los filtros.</td></tr>
        @endforelse
    </tbody>

    @if($logs->hasPages())
        <x-slot:pagination>
            {{ $logs->links() }}
        </x-slot:pagination>
    @endif
    </x-data-table>
</x-page-shell>
