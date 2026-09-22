<x-page-shell max-width="7xl">
    <x-page-header eyebrow="Administración" title="Gestión de clientes" description="Consulta saldos, estados y accesos de los clientes." />
<section class="ui-data-table">
    <div class="ui-data-table__header">
        <div>
            <h2 class="ui-data-table__title">Gestión de clientes</h2>
            <p class="ui-data-table__description">Consulta saldos, estados y accesos de clientes.</p>
        </div>
        <button wire:click="resetFilters" type="button"
            class="ui-secondary-button">
            Limpiar filtros
        </button>
    </div>

    <div class="ui-data-table__filters sm:grid-cols-3 lg:grid-cols-3">
        <div>
            <label class="ui-label mb-1 text-xs" for="clientes-buscar">Buscar</label>
            <input id="clientes-buscar" type="search" wire:model.live.debounce.300ms="buscar"
                placeholder="Buscar por nombre, email o prefijo API Key"
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
        <div></div>
    </div>

    @if($clientes->isEmpty())
        <p class="ui-data-table__empty">No se encontraron clientes con los filtros aplicados.</p>
    @else
        <div class="ui-data-table__scroll">
            <table class="ui-data-table__table">
                <caption class="sr-only">Listado de clientes</caption>
                <thead>
                        <tr>
                        <th scope="col">Email</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Saldo</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Prefijo API Key</th>
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
                            <td class="font-mono text-xs">{{ $cliente->api_key_prefijo ?? '—' }}</td>
                            <td class="whitespace-nowrap tabular-nums">
                                {{ $cliente->consultas_hoy_count ?? 0 }} / {{ $cliente->consultas_count ?? 0 }}
                            </td>
                            <td>
                                <button wire:click="toggleDetalle({{ $cliente->id }})" type="button"
                                    class="ui-secondary-button min-h-9 px-3 py-1.5 text-xs
                                        {{ $expandidoId === $cliente->id ? 'bg-slate-100' : '' }}">
                                    {{ $expandidoId === $cliente->id ? 'Ocultar' : 'Ver detalle' }}
                                </button>
                            </td>
                        </tr>
                        @if($expandidoId === $cliente->id)
                            <tr class="bg-slate-50">
                                <td colspan="7" class="px-4 py-4">
                                    @if($detalle && $detalle->id === $cliente->id)
                                        @php
                                            $tieneApiKey = $detalle->api_key_prefijo || $detalle->api_key_creada || $detalle->api_key_ips_permitidas || $detalle->api_key_alcance;
                                        @endphp

                                        {{-- API Key --}}
                                        <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
                                            <h4 class="ui-section-title mb-3 text-base">API Key</h4>
                                            @if($tieneApiKey)
                                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-sm">
                                                    <div class="flex gap-2">
                                                        <dt class="text-slate-500">Prefijo:</dt>
                                                        <dd class="font-mono text-[#14213d]">{{ $detalle->api_key_prefijo ?? '—' }}</dd>
                                                    </div>
                                                    <div class="flex gap-2">
                                                        <dt class="text-slate-500">Creada:</dt>
                                                        <dd class="text-[#14213d]">{{ $detalle->api_key_creada?->format('d/m/Y H:i') ?? '—' }}</dd>
                                                    </div>
                                                    <div class="flex gap-2">
                                                        <dt class="text-slate-500">Revocada:</dt>
                                                        <dd class="text-[#14213d]">
                                                            @if($detalle->api_key_revocada)
                                                                Sí
                                                                @if($detalle->api_key_revocada_en)
                                                                    <span class="text-slate-500">({{ $detalle->api_key_revocada_en->format('d/m/Y H:i') }})</span>
                                                                @endif
                                                            @else
                                                                No
                                                            @endif
                                                        </dd>
                                                    </div>
                                                    <div class="flex gap-2">
                                                        <dt class="text-slate-500">Último uso:</dt>
                                                        <dd class="text-[#14213d]">{{ $detalle->api_key_ultimo_uso?->format('d/m/Y H:i') ?? '—' }}</dd>
                                                    </div>
                                                    <div class="flex gap-2 sm:col-span-2">
                                                        <dt class="text-slate-500">IPs permitidas:</dt>
                                                        <dd class="text-[#14213d]">
                                                            @if(!empty($detalle->api_key_ips_permitidas))
                                                                {{ implode(', ', $detalle->api_key_ips_permitidas) }}
                                                            @else
                                                                —
                                                            @endif
                                                        </dd>
                                                    </div>
                                                    <div class="flex gap-2 sm:col-span-2">
                                                        <dt class="text-slate-500">Alcance:</dt>
                                                        <dd class="text-[#14213d]">
                                                            @if(!empty($detalle->api_key_alcance))
                                                                {{ implode(', ', $detalle->api_key_alcance) }}
                                                            @else
                                                                —
                                                            @endif
                                                        </dd>
                                                    </div>
                                                </dl>
                                            @else
                                                <p class="text-sm text-slate-500">Sin API Key generada.</p>
                                            @endif
                                        </div>

                                        {{-- Consultas --}}
                                        <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
                                            <h4 class="ui-section-title mb-3 text-base">Últimas consultas</h4>
                                            @if($detalle->consultas->isNotEmpty())
                                                <table class="ui-data-table__table ui-data-table__table--compact">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col">Fecha</th>
                                                            <th scope="col">Tipo</th>
                                                            <th scope="col">Identificador</th>
                                                            <th scope="col">Créditos</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($detalle->consultas as $c)
                                                            <tr>
                                                                <td class="whitespace-nowrap">{{ $c->fecha->format('d/m/Y H:i') }}</td>
                                                                <td><span class="ui-table-badge bg-slate-100 uppercase text-slate-700">{{ $c->tipo }}</span></td>
                                                                <td class="py-1 pr-4 font-mono text-sm">{{ $c->identificador }}</td>
                                                                <td class="font-medium tabular-nums text-rose-700">-{{ $c->creditos_gastados }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <p class="text-sm text-slate-500">Sin consultas.</p>
                                            @endif
                                        </div>

                                        {{-- Recargas --}}
                                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                                            <h4 class="ui-section-title mb-3 text-base">Últimas recargas</h4>
                                            @if($detalle->recargas->isNotEmpty())
                                                <table class="ui-data-table__table ui-data-table__table--compact">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col">Fecha</th>
                                                            <th scope="col">Método</th>
                                                            <th scope="col">Monto USD</th>
                                                            <th scope="col">Créditos</th>
                                                            <th scope="col">Estado</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($detalle->recargas as $r)
                                                            @php
                                                                $colorRecarga = $r->estado->color();
                                                                $estadoBadgeClasses = match($colorRecarga) {
                                                                    'yellow' => 'bg-amber-100 text-amber-800',
                                                                    'green'  => 'bg-emerald-100 text-emerald-800',
                                                                    'red'    => 'bg-rose-100 text-rose-800',
                                                                    'orange' => 'bg-orange-100 text-orange-800',
                                                                    default  => 'bg-slate-100 text-slate-700',
                                                                };
                                                            @endphp
                                                            <tr>
                                                                <td class="whitespace-nowrap">{{ $r->fecha->format('d/m/Y H:i') }}</td>
                                                                <td>{{ $r->metodo }}</td>
                                                                <td>{{ number_format($r->monto_usd, 2) }}</td>
                                                                <td class="font-medium tabular-nums">{{ $r->creditos_obtenidos }}</td>
                                                                <td>
                                                                    <span class="ui-table-badge {{ $estadoBadgeClasses }}">
                                                                        {{ $r->estado->label() }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <p class="text-sm text-slate-500">Sin recargas.</p>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($clientes->hasPages())
        <div class="ui-data-table__pagination">
            {{ $clientes->links() }}
        </div>
    @endif

    <p wire:loading.delay role="status" class="sr-only">Actualizando resultados.</p>
</section>
</x-page-shell>
