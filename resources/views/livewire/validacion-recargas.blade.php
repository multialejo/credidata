<x-page-shell max-width="6xl">
    <x-page-header eyebrow="Administración" title="Transferencias pendientes" description="Revisa cada comprobante y aprueba las transferencias para acreditar los créditos al cliente." />

    @if (session('status'))
        <x-alert variant="success">{{ session('status') }}</x-alert>
    @endif

    @error('aprobacion')
        <x-alert variant="danger">{{ $message }}</x-alert>
    @enderror

    @if($puedeAcreditar)
        @if($recargas->isEmpty())
            <section class="ui-card p-8 text-center">
                <p class="text-base font-semibold text-[#14213d]">No hay transferencias pendientes</p>
                <p class="mt-2 text-sm text-slate-600">Las nuevas solicitudes enviadas por clientes aparecerán acá.</p>
            </section>
        @else
            <section class="ui-card overflow-hidden" aria-label="Transferencias pendientes de validar">
                <div class="overflow-x-auto">
                    <table class="ui-data-table__table min-w-full">
                        <thead>
                            <tr>
                                <th scope="col">Cliente</th>
                                <th scope="col">Monto</th>
                                <th scope="col">Créditos</th>
                                <th scope="col">Referencia</th>
                                <th scope="col">Fecha</th>
                                <th scope="col">Comprobante</th>
                                <th scope="col"><span class="sr-only">Acción</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recargas as $recarga)
                                <tr wire:key="transferencia-pendiente-{{ $recarga->id }}">
                                    <td>
                                        <span class="block font-medium text-[#14213d]">{{ $recarga->cliente?->usuario?->nombre ?? 'Cliente' }}</span>
                                        <span class="block text-xs text-slate-500">{{ $recarga->cliente?->usuario?->email }}</span>
                                    </td>
                                    <td class="whitespace-nowrap tabular-nums">${{ number_format((float) $recarga->monto_usd, 2) }}</td>
                                    <td class="whitespace-nowrap tabular-nums">{{ number_format($recarga->creditos_obtenidos) }}</td>
                                    <td class="font-mono text-sm">{{ $recarga->referencia_externa }}</td>
                                    <td class="whitespace-nowrap">{{ $recarga->created_at?->format('Y-m-d H:i') }}</td>
                                    <td>
                                        @if($recarga->comprobante_url)
                                            <a href="{{ route('admin.recargas.comprobante', $recarga) }}" class="font-medium text-[#3155d9] underline underline-offset-2 hover:text-[#2647c2] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3155d9]" target="_blank" rel="noopener noreferrer">Ver comprobante</a>
                                        @else
                                            <span class="text-sm text-rose-700">No disponible</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <button type="button" wire:click="aprobar({{ $recarga->id }})" wire:loading.attr="disabled" wire:target="aprobar({{ $recarga->id }})" class="ui-primary-button min-h-11 whitespace-nowrap px-4">
                                            <span wire:loading.remove wire:target="aprobar({{ $recarga->id }})">Aprobar y acreditar</span>
                                            <span wire:loading wire:target="aprobar({{ $recarga->id }})">Procesando...</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-4 py-3 sm:px-6">{{ $recargas->links() }}</div>
            </section>
        @endif
    @else
        <p class="ui-alert ui-alert--warning">Solo admin o support pueden validar transferencias.</p>
    @endif
</x-page-shell>
