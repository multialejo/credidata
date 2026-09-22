<x-data-table title="Historial de consultas" description="Consultas realizadas con el consumo de créditos asociado.">
    <x-slot:actions>
        <button wire:click="exportarCsv" class="ui-secondary-button">Exportar CSV</button>
    </x-slot:actions>

    <x-slot:filters>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-700" for="consultas-desde">Desde</label>
            <input id="consultas-desde" type="date" wire:model.live="filtroFechaDesde" class="ui-input w-full">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-700" for="consultas-hasta">Hasta</label>
            <input id="consultas-hasta" type="date" wire:model.live="filtroFechaHasta" class="ui-input w-full">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-700" for="consultas-tipo">Tipo</label>
            <select id="consultas-tipo" wire:model.live="filtroTipo" class="ui-input w-full"><option value="">Todos</option><option value="cedula">Cédula</option><option value="ruc">RUC</option></select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-700" for="consultas-resultado">Resultado</label>
            <select id="consultas-resultado" wire:model.live="filtroResultado" class="ui-input w-full"><option value="">Todos</option><option value="exito">Exitosas</option><option value="fallo">Fallidas</option></select>
        </div>
    </x-slot:filters>

    <thead><tr><th scope="col">Fecha</th><th scope="col">Tipo</th><th scope="col">Identificador</th><th scope="col" class="text-right">Créditos</th><th scope="col">Resultado</th></tr></thead>
    <tbody>
        @forelse($consultas as $c)
            <tr><td class="whitespace-nowrap">{{ $c->fecha->format('d/m/Y H:i') }}</td><td><span class="ui-table-badge bg-slate-100 uppercase text-slate-700">{{ $c->tipo }}</span></td><td class="font-mono text-xs">{{ $c->identificador }}</td><td class="text-right font-semibold text-rose-700 tabular-nums">-{{ $c->creditos_gastados }}</td><td>@if($c->exitosa)<span class="ui-table-badge bg-emerald-100 text-emerald-800">Exitosa</span>@else<span class="ui-table-badge bg-rose-100 text-rose-800">Fallida</span>@endif</td></tr>
        @empty
            <tr><td colspan="5" class="ui-data-table__empty">No hay consultas registradas.</td></tr>
        @endforelse
    </tbody>

    @if($consultas->hasPages())
        <x-slot:pagination>
            {{ $consultas->links() }}
        </x-slot:pagination>
    @endif
</x-data-table>
