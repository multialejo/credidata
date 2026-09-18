<div class="mx-auto max-w-5xl px-4 pt-6 sm:px-6 lg:px-8">
    <div class="mb-6">
        <p class="ui-eyebrow">Créditos</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-[#14213d] sm:text-3xl">Recargar créditos</h1>
    </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_0.65fr]">
            <section class="ui-card p-5 sm:p-7">
                <header class="border-b border-slate-100 pb-6">
                    <p class="text-sm leading-6 text-slate-600">Elige un método de pago y el monto en USD que deseas cargar a tu cuenta.</p>
                </header>

                <div class="pt-6">
                    <p class="mb-3 text-sm font-semibold text-[#14213d]">Método de pago</p>
                    <div role="radiogroup" aria-label="Método de pago" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        @foreach(['paypal' => 'PayPal', 'payphone' => 'PayPhone', 'transferencia' => 'Transferencia'] as $codigo => $etiqueta)
                            @php $disponible = in_array($codigo, $metodosDisponibles, true); @endphp
                            <button type="button" role="radio" aria-checked="{{ $metodo === $codigo ? 'true' : 'false' }}" @if($disponible) wire:click="selectMetodo('{{ $codigo }}')" @endif @class(['group rounded-xl border p-4 text-left transition focus:outline-none focus:ring-2 focus:ring-[#3155d9] focus:ring-offset-2', 'border-[#3155d9] bg-blue-50 ring-1 ring-[#3155d9]/30' => $metodo === $codigo && $disponible, 'border-slate-200 bg-white hover:border-slate-400' => $metodo !== $codigo && $disponible, 'cursor-not-allowed border-slate-200 bg-slate-50 opacity-60' => ! $disponible]) @disabled(! $disponible)>
                                <span class="flex items-center justify-between gap-2 font-semibold text-[#14213d]">{{ $etiqueta }} @if($metodo === $codigo && $disponible)<span class="text-[#3155d9]" aria-hidden="true">✓</span>@endif</span>
                                <span class="mt-1 block text-xs text-slate-500">{{ $disponible ? 'Pago seguro' : 'Próximamente' }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6">
                    <label for="monto" class="block text-sm font-semibold text-[#14213d]">Monto (USD)</label>
                    <div class="relative mt-2">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-500">$</span>
                        <input id="monto" type="number" step="0.01" min="5" wire:model.live="monto" class="ui-input block w-full pl-9 pr-4 py-3" placeholder="10.00" />
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Monto mínimo: $5.00</p>
                    @error('monto')<p class="mt-2 text-sm font-medium text-rose-600" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="mt-7 border-t border-slate-100 pt-6">
                    @if($metodo === 'paypal')
                        <livewire:pay-with-paypal :monto="$monto" :key="'paypal-'.$metodo" />
                    @elseif($metodo === 'payphone')
                        <livewire:pay-with-payphone :monto="$monto" :key="'payphone-'.$metodo" />
                    @endif
                </div>
            </section>

            <aside class="rounded-2xl bg-[#e8edf9] p-6 sm:p-7">
                <p class="ui-eyebrow text-[#3155d9]">Antes de pagar</p>
                <h2 class="mt-3 text-lg font-bold text-[#14213d]">Una recarga, más consultas.</h2>
                <ul class="mt-5 space-y-4 text-sm leading-6 text-slate-700">
                    <li class="flex gap-3"><span class="mt-1 text-[#3155d9]">●</span><span>El saldo se acredita después de confirmar el pago.</span></li>
                    <li class="flex gap-3"><span class="mt-1 text-[#3155d9]">●</span><span>Serás redirigido a la plataforma segura del proveedor.</span></li>
                    <li class="flex gap-3"><span class="mt-1 text-[#3155d9]">●</span><span>Puedes revisar el estado en tu historial de movimientos.</span></li>
                </ul>
            </aside>
        </div>
</div>
