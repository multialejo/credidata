<div>
    @if(!$this->datosTransferencia)
        <div class="ui-data-table__empty">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                <x-icons.arrows-right-left class="h-6 w-6 text-slate-400" />
            </div>
            <p class="mt-4 text-sm font-medium text-slate-600">Información bancaria no configurada</p>
            <p class="mt-1 text-xs text-slate-400">El administrador aún no ha configurado los datos para transferencias. Selecciona otro método de pago o contacta a soporte.</p>
        </div>
    @elseif($solicitudEnviada)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5" role="status" aria-live="polite">
            <p class="text-base font-semibold text-emerald-900">Comprobante enviado</p>
            <p class="mt-2 text-sm leading-6 text-emerald-800">Recibimos tu comprobante de transferencia. El saldo se acreditará cuando soporte confirme el pago.</p>
        </div>
    @else
        @php $datos = $this->datosTransferencia; @endphp
        <div class="space-y-5">
            <section class="rounded-xl border border-slate-200 bg-slate-50 p-4" aria-labelledby="datos-transferencia-title">
                <h3 id="datos-transferencia-title" class="text-sm font-semibold text-[#14213d]">Datos para realizar la transferencia</h3>
                <p class="mt-2 text-sm text-slate-700">Transfiere exactamente <strong>${{ number_format($monto, 2) }}</strong> a esta cuenta:</p>
                <dl class="mt-4 divide-y divide-slate-200 rounded-xl border border-slate-100 bg-white px-4">
                    <div class="py-3"><dt class="text-xs text-slate-500">Banco</dt><dd class="mt-1 text-sm font-semibold text-[#14213d]">{{ $datos['banco'] }} · {{ $datos['tipoCuenta'] }}</dd></div>
                    <div class="py-3"><dt class="text-xs text-slate-500">Número de cuenta</dt><dd class="mt-1 font-mono text-sm font-semibold text-[#14213d]">{{ $datos['numeroCuenta'] }}</dd></div>
                    <div class="py-3"><dt class="text-xs text-slate-500">Titular</dt><dd class="mt-1 text-sm font-semibold text-[#14213d]">{{ $datos['titular'] }}</dd><dd class="text-xs text-slate-500">Cédula: {{ $datos['cedulaTitular'] }}</dd></div>
                </dl>
            </section>

            <form wire:submit="enviarComprobante" class="space-y-4" enctype="multipart/form-data">
                <h3 class="text-sm font-semibold text-[#14213d]">Confirma tu transferencia</h3>
                <p class="text-sm leading-6 text-slate-600">Después de transferir, ingresa la referencia bancaria y adjunta el comprobante. Los {{ number_format($this->creditosEstimados) }} créditos se acreditarán cuando soporte valide el pago.</p>

                <div>
                    <x-input-label for="referenciaBancaria" value="Referencia bancaria" />
                    <x-text-input id="referenciaBancaria" wire:model="referenciaBancaria" type="text" maxlength="100" autocomplete="off" class="mt-1 block min-h-11 w-full" required />
                    @error('referenciaBancaria') <p class="ui-error" role="alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-input-label for="comprobante" value="Comprobante (JPG, PNG o PDF; máximo 10 MB)" />
                    <input id="comprobante" wire:model="comprobante" type="file" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" class="ui-input mt-1 block min-h-11 w-full py-2" required />
                    @error('comprobante') <p class="ui-error" role="alert">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="comprobante" class="ui-help mt-2" role="status">Cargando comprobante...</div>
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="enviarComprobante,comprobante" class="ui-primary-button w-full">
                    <span wire:loading.remove wire:target="enviarComprobante">Enviar comprobante</span>
                    <span wire:loading wire:target="enviarComprobante">Enviando...</span>
                </button>
            </form>
        </div>
    @endif
</div>
