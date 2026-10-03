<x-page-shell max-width="7xl">
    <x-page-header eyebrow="Consultas" title="Historial de consultas" description="Revisa tus consultas y el consumo de créditos asociado." />

    <section class="grid gap-4 sm:grid-cols-3" aria-label="Resumen de consultas">
        <div class="ui-card p-5">
            <p class="text-sm font-medium text-slate-600">Consultas</p>
            <p class="mt-2 text-3xl font-bold tracking-tight text-[#14213d] tabular-nums">{{ number_format($estadisticas['total']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Según los filtros aplicados</p>
        </div>
        <div class="ui-card p-5">
            <p class="text-sm font-medium text-slate-600">Créditos consumidos</p>
            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-500 tabular-nums">{{ number_format($estadisticas['creditos']) }}</p>
            <p class="mt-1 text-xs text-slate-500">En el conjunto filtrado</p>
        </div>
        <div class="ui-card p-5">
            <p class="text-sm font-medium text-slate-600">Tasa de éxito</p>
            <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-700 tabular-nums">{{ $estadisticas['tasa_exito'] }}%</p>
            <p class="mt-1 text-xs text-slate-500">Consultas con respuesta exitosa</p>
        </div>
    </section>

    <x-data-table caption="Consultas registradas">
    <x-slot:filters class="lg:grid-cols-5">
        <div>
            <label class="ui-label mb-1 text-xs" for="consultas-desde">Desde</label>
            <input id="consultas-desde" type="date" wire:model.live="filtroFechaDesde" class="ui-input w-full">
        </div>
        <div>
            <label class="ui-label mb-1 text-xs" for="consultas-hasta">Hasta</label>
            <input id="consultas-hasta" type="date" wire:model.live="filtroFechaHasta" class="ui-input w-full">
        </div>
        <div>
            <label class="ui-label mb-1 text-xs" for="consultas-tipo">Tipo</label>
            <select id="consultas-tipo" wire:model.live="filtroTipo" class="ui-input w-full"><option value="">Todos</option><option value="cedula">Cédula</option><option value="ruc">RUC</option></select>
        </div>
        <div>
            <label class="ui-label mb-1 text-xs" for="consultas-resultado">Resultado</label>
            <select id="consultas-resultado" wire:model.live="filtroResultado" class="ui-input w-full"><option value="">Todos</option><option value="exito">Exitosas</option><option value="fallo">Fallidas</option></select>
        </div>
        <div class="flex items-end justify-end gap-2">
            <button type="button" wire:click="resetFilters" class="ui-secondary-button">Limpiar filtros</button>
            <button type="button" wire:click="exportarCsv" wire:loading.attr="disabled" wire:target="exportarCsv" class="ui-secondary-button">
                <span wire:loading.remove wire:target="exportarCsv">Exportar CSV</span>
                <span wire:loading wire:target="exportarCsv">Exportando...</span>
            </button>
        </div>
    </x-slot:filters>

    <thead><tr><th scope="col">Fecha</th><th scope="col">Tipo</th><th scope="col">Identificador</th><th scope="col">API key</th><th scope="col" class="text-right">Créditos</th><th scope="col">Resultado</th></tr></thead>
    <tbody>
        @forelse($consultas as $c)
            <tr><td class="whitespace-nowrap">{{ $c->fecha->format('d/m/Y H:i') }}</td><td><span class="ui-table-badge bg-slate-100 uppercase text-slate-700">{{ $c->tipo }}</span></td><td class="font-mono text-xs">{{ $c->identificador }}</td><td><span class="block font-medium text-[#14213d]">{{ $c->apiKey?->nombre ?? 'Sin API key asociada' }}</span>@if($c->apiKey)<code class="font-mono text-xs text-slate-500">cd_sk_{{ $c->apiKey->prefijo }}</code>@endif</td><td class="text-right font-semibold text-rose-700 tabular-nums">-{{ $c->creditos_gastados }}</td><td>@if($c->exitosa)<span class="ui-table-badge bg-emerald-100 text-emerald-800">Exitosa</span>@else<span class="ui-table-badge bg-rose-100 text-rose-800">Fallida</span>@endif</td></tr>
        @empty
            <tr><td colspan="6" class="ui-data-table__empty">No hay consultas registradas.</td></tr>
        @endforelse
    </tbody>

    @if($consultas->hasPages())
        <x-slot:pagination>
            {{ $consultas->links() }}
        </x-slot:pagination>
    @endif
    </x-data-table>
</x-page-shell>
