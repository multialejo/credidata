<x-page-shell max-width="7xl">
    <x-page-header eyebrow="Administración" title="Aportes pendientes" description="Revisa los cambios propuestos por colaboradores antes de aplicarlos." />
<x-data-table caption="Aportes por revisar">
    <thead><tr><th scope="col">Identificador</th><th scope="col">Tipo</th><th scope="col">Fecha</th><th scope="col" class="text-right">Acciones</th></tr></thead>
    <tbody>
        @forelse($aportes as $aporte)
            <tr>
                <td class="font-mono text-xs">{{ str_repeat('*', max(0, strlen($aporte->identificador_relacionado) - 4)).substr($aporte->identificador_relacionado, -4) }}</td>
                <td><span class="ui-table-badge bg-slate-100 text-slate-700">{{ $aporte->tipo_dato }}</span></td>
                <td class="whitespace-nowrap">{{ $aporte->fecha?->format('d/m/Y H:i') }}</td>
                <td class="text-right"><button wire:click="ver({{ $aporte->id }})" class="ui-secondary-button px-3 py-1.5 text-xs">Ver detalle</button></td>
            </tr>
        @empty
            <tr><td colspan="4" class="ui-data-table__empty">No hay aportes pendientes.</td></tr>
        @endforelse
    </tbody>
    @if($aportes->hasPages())
        <x-slot:pagination>
            {{ $aportes->links() }}
        </x-slot:pagination>
    @endif
    </x-data-table>

    <x-modal name="contribution-detail" maxWidth="lg" titleId="contribution-detail-title" descriptionId="contribution-detail-description">
        @if($detalleId)
            @php $detalleAporte = $aportes->firstWhere('id', $detalleId); @endphp
            @if($detalleAporte)
                <div class="p-6 sm:p-7">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-5">
                        <div>
                            <p class="ui-eyebrow">Revisión de aporte</p>
                            <h2 id="contribution-detail-title" class="mt-1 text-xl font-bold text-[#14213d]">Detalle del aporte</h2>
                            <p id="contribution-detail-description" class="mt-1 text-sm text-slate-600">Revisa la información antes de tomar una decisión.</p>
                        </div>
                        <button type="button" wire:click="cerrarDetalle" x-on:click="$dispatch('close')" class="ui-secondary-button min-h-11 min-w-11 px-3" aria-label="Cerrar detalle">Cerrar</button>
                    </div>

                    <dl class="mt-5 divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-slate-50 px-4">
                        <div class="grid gap-1 py-3 sm:grid-cols-2 sm:gap-4"><dt class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500">Colaborador</dt><dd class="break-words text-sm text-slate-700">{{ $detalleAporte->colaborador->usuario->email }}</dd></div>
                        <div class="grid gap-1 py-3 sm:grid-cols-2 sm:gap-4"><dt class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500">Valor actual</dt><dd class="break-words text-sm text-slate-700">{{ $valorActual ?: 'Sin información' }}</dd></div>
                        <div class="grid gap-1 py-3 sm:grid-cols-2 sm:gap-4"><dt class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500">Valor propuesto</dt><dd class="break-words text-sm text-slate-700">{{ $detalleAporte->valor }}</dd></div>
                    </dl>

                    <label for="aporte-comentario" class="ui-label mt-5 block">Comentario opcional</label>
                    <textarea id="aporte-comentario" wire:model="comentario" class="ui-input mt-1 w-full" rows="3"></textarea>
                    <div class="mt-5 flex flex-wrap justify-end gap-2">
                        <button wire:click="decidir({{ $detalleAporte->id }}, false)" class="ui-secondary-button border-rose-300 text-rose-700 hover:bg-rose-50">Rechazar</button>
                        <button wire:click="decidir({{ $detalleAporte->id }}, true)" class="ui-primary-button bg-emerald-700 hover:bg-emerald-800">Aprobar</button>
                    </div>
                </div>
            @endif
        @endif
    </x-modal>
</x-page-shell>
