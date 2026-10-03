<x-page-shell max-width="7xl">
    <x-page-header eyebrow="Administración" title="Registro de actividad" description="Trazabilidad de acciones realizadas por usuarios y procesos del sistema." />

    <section class="ui-card p-5 sm:p-6" aria-labelledby="actividad-grafica-title">
        <div class="flex flex-col gap-5 border-b border-slate-100 pb-5 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <h2 id="actividad-grafica-title" class="text-xl font-bold tracking-tight text-[#14213d]">Eventos por día</h2>
                <p id="actividad-grafica-description" class="mt-1 text-sm leading-6 text-slate-600">Cantidad de eventos registrados por día, según los filtros aplicados.</p>
            </div>
            <div class="flex flex-col gap-3 xl:items-end">
                <div class="flex flex-wrap items-end gap-2">
                    <div>
                        <label class="ui-label mb-1 text-xs" for="grafica-desde">Desde</label>
                        <input id="grafica-desde" type="date" wire:model.live="fechaDesde" class="ui-input w-full sm:w-36">
                    </div>
                    <div>
                        <label class="ui-label mb-1 text-xs" for="grafica-hasta">Hasta</label>
                        <input id="grafica-hasta" type="date" wire:model.live="fechaHasta" class="ui-input w-full sm:w-36">
                    </div>
                </div>
                <div class="flex flex-wrap gap-2" aria-label="Rangos rápidos">
                    <button type="button" wire:click="establecerRango('7')" class="ui-secondary-button min-h-9 px-3 py-1 text-xs">7 días</button>
                    <button type="button" wire:click="establecerRango('30')" class="ui-secondary-button min-h-9 px-3 py-1 text-xs">30 días</button>
                    <button type="button" wire:click="establecerRango('90')" class="ui-secondary-button min-h-9 px-3 py-1 text-xs">90 días</button>
                    <button type="button" wire:click="establecerRango('year')" class="ui-secondary-button min-h-9 px-3 py-1 text-xs">Este año</button>
                </div>
            </div>
        </div>

        @if($actividadDiaria)
            <div class="mt-5" data-activity-chart x-init="renderActivityChart($refs.canvas, @js($actividadDiaria))">
                <div class="relative h-72" role="img" aria-labelledby="actividad-grafica-title actividad-grafica-description">
                    <canvas x-ref="canvas" aria-label="Eventos registrados por día"></canvas>
                </div>
                <p class="sr-only">El gráfico muestra {{ count($actividadDiaria) }} días. El máximo registrado fue de {{ $picoActividad['total'] }} eventos el {{ \Carbon\Carbon::parse($picoActividad['fecha'])->format('d/m/Y') }}.</p>
            </div>
        @else
            <p class="mt-5 rounded-xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-600">No hay eventos para graficar en el período seleccionado.</p>
        @endif
    </section>

<x-data-table caption="Actividad registrada">
     <x-slot:filters class="lg:grid-cols-3">
         <div><label class="ui-label mb-1 text-xs" for="log-accion">Acción</label><select id="log-accion" wire:model.live="accion" class="ui-input w-full"><option value="">Todas las acciones</option>@foreach($accionesConocidas as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach</select></div>
         <div><label class="ui-label mb-1 text-xs" for="log-actor">Actor</label><input id="log-actor" type="search" wire:model.live.debounce.300ms="actorEmail" placeholder="Buscar por correo" class="ui-input w-full"></div>
        <div class="flex items-end justify-end gap-2">
            <button type="button" wire:click="resetFilters" class="ui-secondary-button">Limpiar filtros</button>
            <button type="button" wire:click="exportarCsv" wire:loading.attr="disabled" wire:target="exportarCsv" class="ui-secondary-button">
                <span wire:loading.remove wire:target="exportarCsv">Exportar CSV</span>
                <span wire:loading wire:target="exportarCsv">Exportando...</span>
            </button>
        </div>
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
                        <button wire:click="verDetalle({{ $log->id }})" type="button" class="font-semibold text-[#3155d9] underline-offset-2 hover:underline focus:outline-none focus:ring-2 focus:ring-[#3155d9] focus:ring-offset-2">
                            Ver detalle
                        </button>
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

    <x-modal name="log-detail" maxWidth="xl" titleId="log-detail-title" descriptionId="log-detail-description">
        @if($detalleLog)
            <div class="p-6 sm:p-7">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-5">
                    <div>
                        <p class="ui-eyebrow">Detalle de actividad</p>
                        <h2 id="log-detail-title" class="mt-1 text-xl font-bold text-[#14213d]">{{ $detalleLog->accion }}</h2>
                        <p id="log-detail-description" class="mt-1 text-sm text-slate-600">Información registrada el {{ $detalleLog->fecha->format('d/m/Y H:i') }}.</p>
                    </div>
                    <button type="button" wire:click="cerrarDetalle" x-on:click="$dispatch('close')" class="ui-secondary-button min-h-11 min-w-11 px-3" aria-label="Cerrar detalle">Cerrar</button>
                </div>

                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-4 sm:px-5">
                    <x-detail-list :items="$detalleLog->detalle" />
                </div>
            </div>
        @endif
    </x-modal>
</x-page-shell>
