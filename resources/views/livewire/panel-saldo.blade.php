<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Saldo Actual</h3>
            <p class="text-3xl font-bold {{ $saldo > 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ number_format($saldo, 0) }} créditos
            </p>
        </div>
        <a
            href="{{ route('dashboard.recargas') }}"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        >
            Recargar créditos
        </a>
    </div>

    @if(count($ultimosMovimientos) > 0)
        <h4 class="text-md font-semibold text-gray-700 mt-6 mb-2">Últimos movimientos</h4>
        <ul class="divide-y divide-gray-200" role="list">
            @foreach($ultimosMovimientos as $mov)
                <li class="py-2 flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-900">{{ $mov['descripcion'] }}</p>
                        <p class="text-xs text-gray-500">{{ $mov['fecha']->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if(($mov['tipo'] ?? null) === 'recarga')
                            @switch($mov['estado'] ?? null)
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
                                @case('fallida')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Fallida
                                    </span>
                                @break
                            @endswitch
                        @endif
                        <span class="text-sm font-semibold {{ $mov['monto'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $mov['monto'] > 0 ? '+' : '' }}{{ number_format($mov['monto'], 0) }}
                        </span>
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <p class="mt-4 text-sm text-gray-500">Sin movimientos recientes.</p>
    @endif
</div>
