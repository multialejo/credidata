<x-page-shell max-width="7xl">
    <x-page-header eyebrow="Administración" title="Gestión de clientes" description="Consulta saldos, estados y accesos de los clientes." />

    <section class="ui-card p-5 sm:p-6" aria-labelledby="consultas-grafica-title">
        <div class="flex flex-col gap-5 border-b border-slate-100 pb-5 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <h2 id="consultas-grafica-title" class="text-xl font-bold tracking-tight text-[#14213d]">Uso agregado de consultas</h2>
                <p id="consultas-grafica-description" class="mt-1 text-sm leading-6 text-slate-600">Evolución diaria de las consultas realizadas por todos los clientes.</p>
            </div>
            <div class="flex flex-col gap-3 xl:items-end">
                <div class="flex flex-wrap items-end gap-2">
                    <div>
                        <label class="ui-label mb-1 text-xs" for="clientes-grafica-desde">Desde</label>
                        <input id="clientes-grafica-desde" type="date" wire:model.live="graficaFechaDesde" class="ui-input w-full sm:w-36">
                    </div>
                    <div>
                        <label class="ui-label mb-1 text-xs" for="clientes-grafica-hasta">Hasta</label>
                        <input id="clientes-grafica-hasta" type="date" wire:model.live="graficaFechaHasta" class="ui-input w-full sm:w-36">
                    </div>
                </div>
                <div class="flex flex-wrap gap-2" aria-label="Rangos rápidos de consultas">
                    <button type="button" wire:click="establecerRango('7')" class="ui-secondary-button min-h-9 px-3 py-1 text-xs">7 días</button>
                    <button type="button" wire:click="establecerRango('30')" class="ui-secondary-button min-h-9 px-3 py-1 text-xs">30 días</button>
                    <button type="button" wire:click="establecerRango('90')" class="ui-secondary-button min-h-9 px-3 py-1 text-xs">90 días</button>
                    <button type="button" wire:click="establecerRango('year')" class="ui-secondary-button min-h-9 px-3 py-1 text-xs">Este año</button>
                </div>
            </div>
        </div>

        <dl class="mt-5 grid gap-3 sm:grid-cols-3" aria-label="Estadísticas del período">
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <dt class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500">Consultas en el período</dt>
                <dd class="mt-1 text-2xl font-bold tabular-nums text-[#14213d]">{{ number_format($estadisticasConsultas['total']) }}</dd>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <dt class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500">Promedio diario</dt>
                <dd class="mt-1 text-2xl font-bold tabular-nums text-[#14213d]">{{ number_format($estadisticasConsultas['promedio'], 1) }}</dd>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <dt class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500">Clientes activos</dt>
                <dd class="mt-1 text-2xl font-bold tabular-nums text-[#14213d]">{{ number_format($estadisticasConsultas['clientes_activos']) }}</dd>
            </div>
        </dl>

        @if($consultasDiarias)
            <div class="mt-5" data-consultation-chart x-init="renderConsultationChart($refs.canvas, @js($consultasDiarias))">
                <div class="relative h-72" role="img" aria-labelledby="consultas-grafica-title consultas-grafica-description">
                    <canvas x-ref="canvas" aria-label="Consultas realizadas por día y promedio móvil de siete días"></canvas>
                </div>
                <p class="sr-only">El gráfico muestra {{ count($consultasDiarias) }} días de consultas globales, con una línea de promedio móvil de siete días.</p>
            </div>
        @else
            <p class="mt-5 rounded-xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-600">Seleccioná un rango de fechas válido para graficar las consultas.</p>
        @endif
    </section>

<x-data-table caption="Listado de clientes">
    <x-slot:filters class="sm:grid-cols-3 lg:grid-cols-3">
        <div>
            <label class="ui-label mb-1 text-xs" for="clientes-buscar">Buscar</label>
            <input id="clientes-buscar" type="search" wire:model.live.debounce.300ms="buscar"
                placeholder="Buscar por nombre, email o prefijo de API Key"
                class="ui-input w-full">
        </div>
        <div>
            <label class="ui-label mb-1 text-xs" for="clientes-estado">Estado</label>
            <select id="clientes-estado" wire:model.live="estado"
                class="ui-input w-full">
                <option value="">Todos los estados</option>
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
                <option value="suspendido">Suspendido</option>
            </select>
        </div>
        <div class="flex items-end justify-end gap-2">
            <button wire:click="resetFilters" type="button" class="ui-secondary-button">Limpiar filtros</button>
            <button wire:click="exportarCsv" type="button" wire:loading.attr="disabled" wire:target="exportarCsv" class="ui-secondary-button">
                <span wire:loading.remove wire:target="exportarCsv">Exportar CSV</span>
                <span wire:loading wire:target="exportarCsv">Exportando...</span>
            </button>
        </div>
    </x-slot:filters>

    @if($clientes->isEmpty())
        <tbody>
            <tr><td colspan="7" class="ui-data-table__empty">No se encontraron clientes con los filtros aplicados.</td></tr>
        </tbody>
    @else
        <thead>
            <tr>
                <th scope="col">Email</th>
                <th scope="col">Nombre</th>
                <th scope="col">Saldo</th>
                <th scope="col">Estado</th>
                <th scope="col">API Keys</th>
                <th scope="col">Consultas hoy / total</th>
                <th scope="col">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clientes as $cliente)
                <tr>
                    <td class="whitespace-nowrap">{{ $cliente->usuario->email ?? '—' }}</td>
                    <td class="whitespace-nowrap">{{ $cliente->usuario->nombre ?? '—' }}</td>
                    <td class="font-semibold tabular-nums">{{ $cliente->saldo_creditos }}</td>
                    <td>
                        @php $estadoUsuario = $cliente->usuario->estado ?? null; @endphp
                        @if($estadoUsuario === 'activo')
                            <span class="ui-table-badge bg-emerald-100 text-emerald-800">Activo</span>
                        @elseif($estadoUsuario === 'suspendido')
                            <span class="ui-table-badge bg-rose-100 text-rose-800">Suspendido</span>
                        @elseif($estadoUsuario === 'inactivo')
                            <span class="ui-table-badge bg-slate-100 text-slate-700">Inactivo</span>
                        @else
                            <span class="ui-table-badge bg-slate-100 text-slate-700">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap">{{ $cliente->api_keys_count }}</td>
                    <td class="whitespace-nowrap tabular-nums">
                        {{ $cliente->consultas_hoy_count ?? 0 }} / {{ $cliente->consultas_count ?? 0 }}
                    </td>
                    <td>
                        <button wire:click="toggleDetalle({{ $cliente->id }})" type="button"
                            class="ui-secondary-button min-h-9 px-3 py-1.5 text-xs
                                {{ $expandidoId === $cliente->id ? 'bg-slate-100' : '' }}">
                            Ver detalle
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    @endif

    @if($clientes->hasPages())
        <x-slot:pagination>
            {{ $clientes->links() }}
        </x-slot:pagination>
    @endif

</x-data-table>
<x-client-detail-modal :detail="$detalle" />
</x-page-shell>
