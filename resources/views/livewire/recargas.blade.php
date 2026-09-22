<div>
<x-page-shell max-width="5xl">
    <x-page-header eyebrow="Créditos" title="Recargar créditos" description="Elige un método de pago y el monto en USD que deseas cargar a tu cuenta." />

    <div class="grid gap-6 lg:grid-cols-[1fr_0.65fr]">
        <section class="ui-card p-5 sm:p-7">
            <div class="pt-6">
                <p class="ui-label mb-3">Método de pago</p>
                <div role="radiogroup" aria-label="Método de pago" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach($metodosHabilitados as $codigo => $configuracion)
                        @php $disponible = $configuracion['pagable']; @endphp
                        <button type="button" role="radio" aria-checked="{{ $metodo === $codigo ? 'true' : 'false' }}" @if($disponible) wire:click="selectMetodo('{{ $codigo }}')" @endif @class(['group rounded-xl border p-4 text-left transition focus:outline-none focus:ring-2 focus:ring-[#3155d9] focus:ring-offset-2', 'border-[#3155d9] bg-blue-50 ring-1 ring-[#3155d9]/30' => $metodo === $codigo && $disponible, 'border-slate-200 bg-white hover:border-slate-400' => $metodo !== $codigo && $disponible, 'cursor-not-allowed border-slate-200 bg-slate-50 opacity-60' => ! $disponible]) @disabled(! $disponible)>
                            <span class="flex items-start gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#e8edf9] text-[#3155d9] transition group-hover:bg-blue-50">
                                    <x-dynamic-component :component="'icons.'.$configuracion['icon']" class="h-5 w-5" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-[#14213d]">{{ $configuracion['label'] }}</span>
                                    <span class="mt-0.5 block text-xs text-slate-500">{{ $configuracion['detail'] }}</span>
                                </span>
                                <span aria-hidden="true" @class(['mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border', 'border-[#3155d9]' => $metodo === $codigo && $disponible, 'border-slate-300' => $metodo !== $codigo || ! $disponible])>
                                    @if($metodo === $codigo && $disponible)
                                        <span class="h-2.5 w-2.5 rounded-full bg-[#3155d9]"></span>
                                    @endif
                                </span>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="mt-6">
                <label for="monto" class="ui-label">Monto (USD)</label>
                <div class="relative mt-2">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-500">$</span>
                    <input id="monto" type="text" inputmode="decimal" pattern="[0-9]*[.,]?[0-9]*" wire:model.live="monto" class="ui-input block w-full pl-9 pr-4 py-3" placeholder="10.00" aria-describedby="monto-minimo" />
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1">
                    <p id="monto-minimo" class="ui-help">Monto mínimo: ${{ number_format($recargaMinimaUsd, 2) }}</p>
                    <p class="ui-help {{ $monto > $recargaMaximaPayphoneUsd ? 'font-medium text-rose-600' : '' }}">Monto máximo: ${{ number_format($recargaMaximaPayphoneUsd, 2) }}</p>
                </div>
                @if($monto > $recargaMaximaPayphoneUsd)
                    <p class="ui-error" role="alert">El monto no puede exceder ${{ number_format($recargaMaximaPayphoneUsd, 2) }} USD por transacción.</p>
                @endif
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($montosSugeridos as $sugerido)
                        <button type="button" wire:click="selectMonto({{ $sugerido }})" class="min-h-11 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-[#14213d] transition hover:border-[#3155d9] hover:text-[#3155d9] focus:outline-none focus:ring-2 focus:ring-[#3155d9] focus:ring-offset-2">
                            ${{ number_format($sugerido, 0) }}
                        </button>
                    @endforeach
                </div>
                 @error('monto')<p class="ui-error" role="alert">{{ $message }}</p>@enderror
            </div>

            @if(in_array($metodo, $metodosDisponibles, true))
            <div class="mt-7 border-t border-slate-100 pt-6">
                @if($this->montoValido)
                    <p class="mb-4 flex items-center justify-between gap-4 rounded-xl bg-[#e8edf9] px-4 py-3 text-sm leading-6" aria-live="polite">
                        <span class="font-medium text-[#14213d]">Total a pagar</span>
                        <span class="font-semibold text-[#3155d9]">${{ number_format($this->montoFloat(), 2) }} → {{ number_format($this->creditosEstimados) }} créditos</span>
                    </p>
                @endif
                @if($metodo === 'paypal')
                    <livewire:pay-with-paypal :monto="$this->montoFloat()" :key="'paypal-'.$metodo" />
                @elseif($metodo === 'payphone')
                    <livewire:pay-with-payphone :monto="$this->montoFloat()" :key="'payphone-'.$metodo" />
                @elseif($metodo === 'tarjeta')
                    <livewire:pay-with-payphone :monto="$this->montoFloat()" :solo-tarjeta="true" :key="'tarjeta-'.$metodo" />
                @elseif($metodo === 'transferencia')
                    @if($this->montoValido)
                         <button
                             type="button"
                             wire:click="abrirModalTransferencia"
                            wire:loading.attr="disabled"
                            wire:target="abrirModalTransferencia"
                            class="ui-primary-button w-full"
                        >
                            <x-icons.arrows-right-left class="h-5 w-5" />
                            <span wire:loading.remove wire:target="abrirModalTransferencia">Mostrar datos bancarios</span>
                            <span wire:loading wire:target="abrirModalTransferencia">Cargando datos...</span>
                        </button>
                    @else
                        <button
                            type="button"
                            disabled
                            class="ui-secondary-button w-full cursor-not-allowed bg-slate-50 text-slate-400"
                        >
                            <x-icons.arrows-right-left class="h-5 w-5" />
                            Mostrar datos bancarios
                        </button>
                        <p class="ui-help text-center">Ingresa un monto válido para ver los datos de transferencia.</p>
                    @endif
                @endif
            </div>
@endif
        </section>

        <aside class="h-fit rounded-2xl bg-[#e8edf9] p-6 sm:p-7" aria-labelledby="recargas-info-title">
            <p class="ui-eyebrow text-[#3155d9]">Antes de pagar</p>
            <h2 id="recargas-info-title" class="mt-3 text-lg font-bold text-[#14213d]">Características de las recargas</h2>
            <ul class="mt-5 space-y-4 text-sm leading-6 text-slate-700">
                <li class="flex gap-3"><span class="mt-1 text-[#3155d9]">●</span><span>Elige el monto y mira cuántos créditos recibirás.</span></li>
                <li class="flex gap-3"><span class="mt-1 text-[#3155d9]">●</span><span>El saldo se acredita después de confirmar el pago.</span></li>
                <li class="flex gap-3"><span class="mt-1 text-[#3155d9]">●</span><span>Serás redirigido a la plataforma segura del proveedor.</span></li>
                <li class="flex gap-3"><span class="mt-1 text-[#3155d9]">●</span><span>Puedes revisar el estado en tu historial de movimientos.</span></li>
            </ul>
        </aside>
    </div>
</x-page-shell>

<div
    wire:key="transferencia-modal-container"
    x-data
    x-on:keydown.escape.window="$wire.cerrarModalTransferencia()"
>
    <div
        wire:show="mostrarModalTransferencia"
        wire:cloak
        class="fixed inset-0 z-50 overflow-y-auto px-4 py-4 sm:py-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="transferencia-modal-title"
    >
        <div class="fixed inset-0 bg-[#14213d]/60" aria-hidden="true" wire:click="cerrarModalTransferencia"></div>

        <div class="relative z-10 flex min-h-full items-center justify-center">
            <div class="flex max-h-[calc(100dvh-2rem)] w-full max-w-xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl sm:max-h-[calc(100dvh-3rem)]">
                <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-4 py-3 sm:px-6 sm:py-4">
                    <h2 id="transferencia-modal-title" class="text-lg font-bold text-[#14213d]">Transferencia bancaria</h2>
                    <button
                        type="button"
                         wire:click="cerrarModalTransferencia"
                        aria-label="Cerrar información de transferencia bancaria"
                        class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3155d9] focus-visible:ring-offset-2"
                    >
                        <svg class="h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="min-h-0 overflow-y-auto overscroll-contain px-4 py-5 sm:px-6">
                    @if($mostrarModalTransferencia && $datosTransferencia)
                        <livewire:pay-with-transferencia :monto="$this->montoFloat()" :key="'transferencia-modal-'.$this->montoFloat()" />
                    @else
                        <div class="text-center py-8">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                                <x-icons.arrows-right-left class="h-6 w-6 text-slate-400" />
                            </div>
                            <p class="mt-4 text-sm font-medium text-slate-600">Información bancaria no configurada</p>
                            <p class="mt-1 text-xs text-slate-400">El administrador aún no ha configurado los datos para transferencias. Selecciona otro método de pago o contacta a soporte.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</div>
