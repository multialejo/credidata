<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recibos de Recarga</h3>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 text-left">
                    <th class="py-2 pr-4 font-medium text-gray-600">Fecha</th>
                    <th class="py-2 pr-4 font-medium text-gray-600">Método</th>
                    <th class="py-2 pr-4 font-medium text-gray-600">Monto USD</th>
                    <th class="py-2 pr-4 font-medium text-gray-600">Créditos</th>
                    <th class="py-2 font-medium text-gray-600">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recargas as $r)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-2 pr-4 whitespace-nowrap text-gray-700">
                            {{ $r->fecha->format('d/m/Y H:i') }}
                        </td>
                        <td class="py-2 pr-4 capitalize">{{ $r->metodo }}</td>
                        <td class="py-2 pr-4">
                            {{ $r->monto_usd > 0 ? '$' . number_format($r->monto_usd, 2) : '—' }}
                        </td>
                        <td class="py-2 pr-4 font-medium text-green-600">
                            +{{ number_format($r->creditos_obtenidos, 0) }}
                        </td>
                        <td class="py-2">
                            @switch($r->estado)
                                @case('completada')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Completada
                                    </span>
                                @break
                                @case('pendiente')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Pendiente
                                    </span>
                                @break
                                @case('rechazada')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Rechazada
                                    </span>
                                @break
                                @default
                                    {{ $r->estado }}
                            @endswitch
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-500">
                            No hay recargas registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($recargas->hasPages())
        <div class="mt-4">
            {{ $recargas->links() }}
        </div>
    @endif
</div>
