<x-page-shell max-width="7xl">
    <x-page-header eyebrow="Administración" title="Gestión de clientes" description="Consulta saldos, estados y accesos de los clientes." />
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
