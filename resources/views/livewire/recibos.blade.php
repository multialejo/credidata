<x-page-shell max-width="7xl">
    <x-page-header eyebrow="Créditos" title="Recibos de recarga" description="Consulta tus recargas, créditos acreditados y estados de pago." />

    <x-data-table caption="Recargas registradas">
    <thead>
        <tr>
            <th scope="col">Fecha</th>
            <th scope="col">Método</th>
            <th scope="col" class="text-right">Monto USD</th>
            <th scope="col" class="text-right">Créditos</th>
            <th scope="col">Estado</th>
            <th scope="col">Recibo</th>
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
                <td>
                    @if($r->estado === \App\Enums\EstadoRecarga::Completada)
                        <a
                            href="{{ route('dashboard.recibos.pdf', $r) }}"
                            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-700 hover:text-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700"
                            aria-label="Descargar recibo PDF de la recarga del {{ $r->fecha->format('d/m/Y') }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4m-4 6v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
                            </svg>
                            Descargar PDF
                        </a>
                    @else
                        <span class="text-sm text-slate-500">No disponible</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="ui-data-table__empty">No hay recargas registradas.</td>
            </tr>
        @endforelse
    </tbody>

    @if($recargas->hasPages())
        <x-slot:pagination>{{ $recargas->links() }}</x-slot:pagination>
    @endif
    </x-data-table>
</x-page-shell>
