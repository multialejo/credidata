<div>
@if(!$this->datosTransferencia)
    <div class="text-center py-8">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
            <x-icons.arrows-right-left class="h-6 w-6 text-slate-400" />
        </div>
        <p class="mt-4 text-sm font-medium text-slate-600">Información bancaria no configurada</p>
        <p class="mt-1 text-xs text-slate-400">El administrador aún no ha configurado los datos para transferencias. Selecciona otro método de pago o contacta a soporte.</p>
    </div>
@else
    @php $datos = $this->datosTransferencia; @endphp
    {{-- Step 1: Datos bancarios --}}
    <div class="space-y-5">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Paso 1 — Transferir</p>
            <p class="mt-2 text-sm text-slate-700">Transfiere exactamente el monto indicado a la siguiente cuenta:</p>

            @if($this->montoValido)
                <div class="mt-3 rounded-lg bg-[#e8edf9] px-4 py-3">
                    <p class="text-xs text-slate-500">Monto a transferir</p>
                    <p class="text-lg font-bold text-[#3155d9]">${{ number_format($monto, 2) }}</p>
                </div>
            @endif

            <div class="mt-4 space-y-3">
                {{-- Banco --}}
                <div class="flex items-center justify-between rounded-lg bg-white px-4 py-3 border border-slate-100">
                    <div>
                        <p class="text-xs text-slate-500">Banco</p>
                        <p class="text-sm font-semibold text-[#14213d]">{{ $datos['banco'] }}</p>
                        <p class="text-xs text-slate-400">{{ $datos['tipoCuenta'] }}</p>
                    </div>
                </div>

                {{-- Número de cuenta --}}
                <div class="flex items-center justify-between rounded-lg bg-white px-4 py-3 border border-slate-100">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-slate-500">Número de cuenta</p>
                        <p class="text-sm font-semibold text-[#14213d] font-mono">{{ $datos['numeroCuenta'] }}</p>
                    </div>
                    <button type="button" x-data="{ copied: false }" x-on:click="copied = true; navigator.clipboard.writeText('{{ $datos['numeroCuenta'] }}'); setTimeout(() => copied = false, 2000)" class="ml-3 shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#3155d9] focus:ring-offset-2">
                        <span x-show="!copied">Copiar</span>
                        <span x-show="copied" class="text-emerald-600">Copiado</span>
                    </button>
                </div>

                {{-- Titular --}}
                <div class="flex items-center justify-between rounded-lg bg-white px-4 py-3 border border-slate-100">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-slate-500">Titular</p>
                        <p class="text-sm font-semibold text-[#14213d]">{{ $datos['titular'] }}</p>
                        <p class="text-xs text-slate-400">Cédula: {{ $datos['cedulaTitular'] }}</p>
                    </div>
                    <button type="button" x-data="{ copied: false }" x-on:click="copied = true; navigator.clipboard.writeText('{{ $datos['cedulaTitular'] }}'); setTimeout(() => copied = false, 2000)" class="ml-3 shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#3155d9] focus:ring-offset-2">
                        <span x-show="!copied">Copiar</span>
                        <span x-show="copied" class="text-emerald-600">Copiado</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Step 2: Enviar comprobante --}}
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Paso 2 — Enviar comprobante</p>
            <p class="mt-2 text-sm text-slate-700">Una vez realizado el depósito, envía el comprobante por WhatsApp. La validación toma entre 5 y 10 minutos.</p>

            @if($this->montoValido && $this->whatsappLink)
                <a href="{{ $this->whatsappLink }}" target="_blank" rel="noopener noreferrer"
                   class="mt-4 flex items-center justify-center gap-2 w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Enviar imagen del comprobante
                </a>
            @endif
        </div>
    </div>
@endif
</div>
