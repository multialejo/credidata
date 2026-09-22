<x-page-shell max-width="7xl">
    <x-page-header eyebrow="Créditos" title="Recibos de recarga" description="Consulta tus recargas, créditos acreditados y estados de pago." />

    <x-data-table title="Recargas registradas" description="Cada movimiento conserva su método, monto y estado.">
    <thead>
        <tr>
            <th scope="col">Fecha</th>
            <th scope="col">Método</th>
            <th scope="col" class="text-right">Monto USD</th>
            <th scope="col" class="text-right">Créditos</th>
            <th scope="col">Estado</th>
        </tr>
    </thead>
    <tbody>
        @forelse($recargas as $r)
            <tr>
                <td class="whitespace-nowrap">{{ $r->fecha->format('d/m/Y H:i') }}</td>
                <td class="capitalize">{{ $r->metodo }}</td>
                <td class="text-right tabular-nums">{{ $r->monto_usd > 0 ? '$' . number_format($r->monto_usd, 2) : '—' }}</td>
                <td class="text-right font-semibold text-emerald-700 tabular-nums">+{{ number_format($r->creditos_obtenidos, 0) }}</td>
                <td>
                    @switch($r->estado)
                        @case(\App\Enums\EstadoRecarga::Completada)
                            <span class="ui-table-badge bg-emerald-100 text-emerald-800">Completada</span>
                        @break
                        @case(\App\Enums\EstadoRecarga::Pendiente)
                            <span class="ui-table-badge bg-amber-100 text-amber-800">Pendiente</span>
                        @break
                        @case(\App\Enums\EstadoRecarga::Rechazada)
                            <span class="ui-table-badge bg-rose-100 text-rose-800">Rechazada</span>
                        @break
                        @case(\App\Enums\EstadoRecarga::Fallida)
                            <span class="ui-table-badge bg-rose-100 text-rose-800">Pago no completado</span>
                        @break
                        @default
                            <span class="ui-table-badge bg-slate-100 text-slate-700">{{ $r->estado->value ?? $r->estado }}</span>
                    @endswitch
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="ui-data-table__empty">No hay recargas registradas.</td>
            </tr>
        @endforelse
    </tbody>

    @if($recargas->hasPages())
        <x-slot:pagination>{{ $recargas->links() }}</x-slot:pagination>
    @endif
    </x-data-table>
</x-page-shell>
