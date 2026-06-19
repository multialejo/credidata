<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Saldo Actual</h3>

    <p class="text-3xl font-bold {{ $saldo > 0 ? 'text-green-600' : 'text-red-600' }}">
        {{ number_format($saldo, 0) }} créditos
    </p>

    @if(count($ultimosMovimientos) > 0)
        <h4 class="text-md font-semibold text-gray-700 mt-6 mb-2">Últimos movimientos</h4>
        <ul class="divide-y divide-gray-200" role="list">
            @foreach($ultimosMovimientos as $mov)
                <li class="py-2 flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-900">{{ $mov['descripcion'] }}</p>
                        <p class="text-xs text-gray-500">{{ $mov['fecha']->format('d/m/Y H:i') }}</p>
                    </div>
                    <span class="text-sm font-semibold {{ $mov['monto'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $mov['monto'] > 0 ? '+' : '' }}{{ number_format($mov['monto'], 0) }}
                    </span>
                </li>
            @endforeach
        </ul>
    @else
        <p class="mt-4 text-sm text-gray-500">Sin movimientos recientes.</p>
    @endif
</div>
