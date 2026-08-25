<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Gestión de Clientes</h3>
        <button wire:click="resetFilters" type="button"
            class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
            Limpiar filtros
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
        <div>
            <label class="block text-xs text-gray-600 mb-1">Buscar</label>
            <input type="text" wire:model.live="buscar"
                placeholder="Buscar por nombre, email o prefijo API Key"
                class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-xs text-gray-600 mb-1">Estado</label>
            <select wire:model.live="estado"
                class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Todos los estados</option>
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
                <option value="suspendido">Suspendido</option>
            </select>
        </div>
        <div></div>
    </div>

    @if($clientes->isEmpty())
        <p class="text-center text-gray-500 py-8">No se encontraron clientes con los filtros aplicados.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left">
                        <th class="py-2 pr-4 font-medium text-gray-600">Email</th>
                        <th class="py-2 pr-4 font-medium text-gray-600">Nombre</th>
                        <th class="py-2 pr-4 font-medium text-gray-600">Saldo</th>
                        <th class="py-2 pr-4 font-medium text-gray-600">Estado</th>
                        <th class="py-2 pr-4 font-medium text-gray-600">Prefijo API Key</th>
                        <th class="py-2 pr-4 font-medium text-gray-600">Consultas hoy / total</th>
                        <th class="py-2 font-medium text-gray-600">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clientes as $cliente)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-2 pr-4 text-gray-700 whitespace-nowrap">{{ $cliente->usuario->email ?? '—' }}</td>
                            <td class="py-2 pr-4 text-gray-700 whitespace-nowrap">{{ $cliente->usuario->nombre ?? '—' }}</td>
                            <td class="py-2 pr-4 font-medium text-gray-900">{{ $cliente->saldo_creditos }}</td>
                            <td class="py-2 pr-4">
                                @php $estadoUsuario = $cliente->usuario->estado ?? null; @endphp
                                @if($estadoUsuario === 'activo')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Activo</span>
                                @elseif($estadoUsuario === 'suspendido')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Suspendido</span>
                                @elseif($estadoUsuario === 'inactivo')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactivo</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">—</span>
                                @endif
                            </td>
                            <td class="py-2 pr-4 font-mono text-sm text-gray-700">{{ $cliente->api_key_prefijo ?? '—' }}</td>
                            <td class="py-2 pr-4 text-gray-700 whitespace-nowrap">
                                {{ $cliente->consultas_hoy_count ?? 0 }} / {{ $cliente->consultas_count ?? 0 }}
                            </td>
                            <td class="py-2">
                                <button wire:click="toggleDetalle({{ $cliente->id }})" type="button"
                                    class="px-3 py-1 text-xs font-medium rounded-md border focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-indigo-500
                                        {{ $expandidoId === $cliente->id ? 'bg-gray-200 text-gray-800 border-gray-300' : 'bg-white text-indigo-700 border-indigo-300 hover:bg-indigo-50' }}">
                                    {{ $expandidoId === $cliente->id ? 'Ocultar' : 'Ver detalle' }}
                                </button>
                            </td>
                        </tr>
                        @if($expandidoId === $cliente->id)
                            <tr class="bg-gray-50">
                                <td colspan="7" class="px-4 py-4">
                                    @if($detalle && $detalle->id === $cliente->id)
                                        @php
                                            $tieneApiKey = $detalle->api_key_prefijo || $detalle->api_key_creada || $detalle->api_key_ips_permitidas || $detalle->api_key_alcance;
                                        @endphp

                                        {{-- API Key --}}
                                        <div class="mb-4 bg-white border border-gray-200 rounded-md p-3">
                                            <h4 class="text-sm font-semibold text-gray-900 mb-2">API Key</h4>
                                            @if($tieneApiKey)
                                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-sm">
                                                    <div class="flex gap-2">
                                                        <dt class="text-gray-600">Prefijo:</dt>
                                                        <dd class="font-mono text-gray-900">{{ $detalle->api_key_prefijo ?? '—' }}</dd>
                                                    </div>
                                                    <div class="flex gap-2">
                                                        <dt class="text-gray-600">Creada:</dt>
                                                        <dd class="text-gray-900">{{ $detalle->api_key_creada?->format('d/m/Y H:i') ?? '—' }}</dd>
                                                    </div>
                                                    <div class="flex gap-2">
                                                        <dt class="text-gray-600">Revocada:</dt>
                                                        <dd class="text-gray-900">
                                                            @if($detalle->api_key_revocada)
                                                                Sí
                                                                @if($detalle->api_key_revocada_en)
                                                                    <span class="text-gray-500">({{ $detalle->api_key_revocada_en->format('d/m/Y H:i') }})</span>
                                                                @endif
                                                            @else
                                                                No
                                                            @endif
                                                        </dd>
                                                    </div>
                                                    <div class="flex gap-2">
                                                        <dt class="text-gray-600">Último uso:</dt>
                                                        <dd class="text-gray-900">{{ $detalle->api_key_ultimo_uso?->format('d/m/Y H:i') ?? '—' }}</dd>
                                                    </div>
                                                    <div class="flex gap-2 sm:col-span-2">
                                                        <dt class="text-gray-600">IPs permitidas:</dt>
                                                        <dd class="text-gray-900">
                                                            @if(!empty($detalle->api_key_ips_permitidas))
                                                                {{ implode(', ', $detalle->api_key_ips_permitidas) }}
                                                            @else
                                                                —
                                                            @endif
                                                        </dd>
                                                    </div>
                                                    <div class="flex gap-2 sm:col-span-2">
                                                        <dt class="text-gray-600">Alcance:</dt>
                                                        <dd class="text-gray-900">
                                                            @if(!empty($detalle->api_key_alcance))
                                                                {{ implode(', ', $detalle->api_key_alcance) }}
                                                            @else
                                                                —
                                                            @endif
                                                        </dd>
                                                    </div>
                                                </dl>
                                            @else
                                                <p class="text-sm text-gray-500">Sin API Key generada.</p>
                                            @endif
                                        </div>

                                        {{-- Consultas --}}
                                        <div class="mb-4 bg-white border border-gray-200 rounded-md p-3">
                                            <h4 class="text-sm font-semibold text-gray-900 mb-2">Últimas consultas</h4>
                                            @if($detalle->consultas->isNotEmpty())
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="border-b border-gray-200 text-left">
                                                            <th class="py-1 pr-4 font-medium text-gray-600">Fecha</th>
                                                            <th class="py-1 pr-4 font-medium text-gray-600">Tipo</th>
                                                            <th class="py-1 pr-4 font-medium text-gray-600">Identificador</th>
                                                            <th class="py-1 font-medium text-gray-600">Créditos</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($detalle->consultas as $c)
                                                            <tr class="border-b border-gray-100">
                                                                <td class="py-1 pr-4 text-gray-700 whitespace-nowrap">{{ $c->fecha->format('d/m/Y H:i') }}</td>
                                                                <td class="py-1 pr-4"><span class="uppercase text-xs font-medium bg-gray-100 px-2 py-0.5 rounded">{{ $c->tipo }}</span></td>
                                                                <td class="py-1 pr-4 font-mono text-sm">{{ $c->identificador }}</td>
                                                                <td class="py-1 font-medium text-red-600">-{{ $c->creditos_gastados }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <p class="text-sm text-gray-500">Sin consultas.</p>
                                            @endif
                                        </div>

                                        {{-- Recargas --}}
                                        <div class="bg-white border border-gray-200 rounded-md p-3">
                                            <h4 class="text-sm font-semibold text-gray-900 mb-2">Últimas recargas</h4>
                                            @if($detalle->recargas->isNotEmpty())
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="border-b border-gray-200 text-left">
                                                            <th class="py-1 pr-4 font-medium text-gray-600">Fecha</th>
                                                            <th class="py-1 pr-4 font-medium text-gray-600">Método</th>
                                                            <th class="py-1 pr-4 font-medium text-gray-600">Monto USD</th>
                                                            <th class="py-1 pr-4 font-medium text-gray-600">Créditos</th>
                                                            <th class="py-1 font-medium text-gray-600">Estado</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($detalle->recargas as $r)
                                                            @php
                                                                $colorRecarga = $r->estado->color();
                                                                $estadoBadgeClasses = match($colorRecarga) {
                                                                    'yellow' => 'bg-yellow-100 text-yellow-800',
                                                                    'green'  => 'bg-green-100 text-green-800',
                                                                    'red'    => 'bg-red-100 text-red-800',
                                                                    'orange' => 'bg-orange-100 text-orange-800',
                                                                    default  => 'bg-gray-100 text-gray-800',
                                                                };
                                                            @endphp
                                                            <tr class="border-b border-gray-100">
                                                                <td class="py-1 pr-4 text-gray-700 whitespace-nowrap">{{ $r->fecha->format('d/m/Y H:i') }}</td>
                                                                <td class="py-1 pr-4 text-gray-700">{{ $r->metodo }}</td>
                                                                <td class="py-1 pr-4 text-gray-700">{{ number_format($r->monto_usd, 2) }}</td>
                                                                <td class="py-1 pr-4 font-medium text-gray-900">{{ $r->creditos_obtenidos }}</td>
                                                                <td class="py-1">
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $estadoBadgeClasses }}">
                                                                        {{ $r->estado->label() }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <p class="text-sm text-gray-500">Sin recargas.</p>
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
        <div class="mt-4">
            {{ $clientes->links() }}
        </div>
    @endif
</div>
