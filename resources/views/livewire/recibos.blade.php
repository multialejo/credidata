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
                            title="Descargar PDF"
                            class="group relative inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl text-slate-600 transition hover:bg-slate-100 hover:text-[#3155d9] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#3155d9]"
                            aria-label="Descargar recibo PDF de la recarga del {{ $r->fecha->format('d/m/Y') }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h6l4.25 4.25v10.5A1.75 1.75 0 0 1 15.5 20.25h-8A1.75 1.75 0 0 1 5.75 18.5v-13A1.75 1.75 0 0 1 7.5 3.75Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 4v4.5h4.5M8.25 14.25h7.5m-7.5 2.5h5.5" />
                            </svg>
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
