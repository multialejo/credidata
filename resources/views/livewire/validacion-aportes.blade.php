<x-data-table title="Aportes pendientes" description="Revisá los cambios propuestos por colaboradores antes de aplicarlos.">
    <thead><tr><th scope="col">Identificador</th><th scope="col">Tipo</th><th scope="col">Fecha</th><th scope="col" class="text-right">Acciones</th></tr></thead>
    <tbody>
        @forelse($aportes as $aporte)
            <tr>
                <td class="font-mono text-xs">{{ str_repeat('*', max(0, strlen($aporte->identificador_relacionado) - 4)).substr($aporte->identificador_relacionado, -4) }}</td>
                <td><span class="ui-table-badge bg-slate-100 text-slate-700">{{ $aporte->tipo_dato }}</span></td>
                <td class="whitespace-nowrap">{{ $aporte->fecha?->format('d/m/Y H:i') }}</td>
                <td class="text-right"><button wire:click="ver({{ $aporte->id }})" class="ui-secondary-button px-3 py-1.5 text-xs">{{ $detalleId === $aporte->id ? 'Ocultar detalle' : 'Ver detalle' }}</button></td>
            </tr>
            @if($detalleId === $aporte->id)
                <tr class="bg-slate-50 hover:bg-slate-50"><td colspan="4" class="p-4">
                    <div class="space-y-2 text-sm text-slate-700"><p><span class="font-semibold text-[#14213d]">Colaborador:</span> {{ $aporte->colaborador->usuario->email }}</p><p><span class="font-semibold text-[#14213d]">Valor actual:</span> {{ $valorActual }}</p><p><span class="font-semibold text-[#14213d]">Valor propuesto:</span> {{ $aporte->valor }}</p><textarea wire:model="comentario" class="ui-input mt-2 w-full" placeholder="Comentario opcional"></textarea><div class="flex flex-wrap gap-2"><button wire:click="decidir({{ $aporte->id }}, true)" class="ui-primary-button bg-emerald-700 hover:bg-emerald-800">Aprobar</button><button wire:click="decidir({{ $aporte->id }}, false)" class="ui-secondary-button border-rose-300 text-rose-700 hover:bg-rose-50">Rechazar</button></div></div>
                </td></tr>
            @endif
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
