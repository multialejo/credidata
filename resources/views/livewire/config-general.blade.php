<x-page-shell max-width="7xl">
    <x-page-header eyebrow="Administración" title="Configuración general" description="Controla los métodos de pago y los parámetros globales del sistema." />
    @php
        $transferenciaConfigurada = filled($transferenciaBanco) && filled($transferenciaNumeroCuenta);
        $etiquetasParametros = [
            'costoConsultaBase' => __('Costo base de consulta'),
            'tasaCambioUsdCreditos' => __('Tasa de cambio USD a créditos'),
            'ttlDatosExternosSegundos' => __('Duración de caché de datos externos'),
        ];
    @endphp

    @if (session('status'))
        <div class="ui-alert ui-alert--success flex items-start gap-3" role="status" aria-live="polite">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span class="font-medium">{{ session('status') }}</span>
        </div>
    @endif

    <section class="ui-card" aria-labelledby="pagos-heading">
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-[#3155d9]">
                    <x-icons.credit-card class="h-5 w-5" />
                </span>
                <div>
                    <h2 id="pagos-heading" class="text-lg font-bold text-[#14213d]">{{ __('Métodos de pago') }}</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-5 text-slate-500">{{ __('Define qué opciones aparecen cuando un cliente recarga créditos.') }}</p>
                </div>
            </div>
        </div>
        <div class="grid gap-3 p-5 sm:grid-cols-2 sm:p-6">
            @foreach($estadosMetodosPago as $codigo => $metodo)
                <button
                    type="button"
                    wire:click="toggleMetodoPago('{{ $codigo }}')"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-60"
                    @class([
                        'group flex min-h-[76px] items-center justify-between gap-4 rounded-xl border px-4 py-3.5 text-left transition focus:outline-none focus:ring-2 focus:ring-[#3155d9] focus:ring-offset-2',
                        'border-emerald-200 bg-emerald-50/70 hover:border-emerald-300' => $metodo['habilitado'],
                        'border-slate-200 bg-slate-50 hover:border-slate-300' => ! $metodo['habilitado'],
                    ])
                    aria-pressed="{{ $metodo['habilitado'] ? 'true' : 'false' }}"
                >
                    <span class="flex min-w-0 items-center gap-3">
                        <span @class([
                            'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-bold',
                            'bg-white text-emerald-700' => $metodo['habilitado'],
                            'bg-slate-200 text-slate-500' => ! $metodo['habilitado'],
                        ])>{{ str($metodo['label'])->substr(0, 1) }}</span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-[#14213d]">{{ $metodo['label'] }}</span>
                            <span class="mt-0.5 block text-xs text-slate-500">{{ $metodo['habilitado'] ? __('Visible para clientes') : __('No visible para clientes') }}</span>
                        </span>
                    </span>
                    <span class="flex shrink-0 items-center gap-2">
                        <span @class([
                            'text-xs font-semibold',
                            'text-emerald-700' => $metodo['habilitado'],
                            'text-slate-500' => ! $metodo['habilitado'],
                        ])>{{ $metodo['habilitado'] ? __('Visible') : __('Oculto') }}</span>
                        <span @class([
                            'relative h-6 w-11 rounded-full transition-colors',
                            'bg-emerald-500' => $metodo['habilitado'],
                            'bg-slate-300' => ! $metodo['habilitado'],
                        ]) aria-hidden="true">
                            <span @class([
                                'absolute top-1 h-4 w-4 rounded-full bg-white shadow-sm transition-transform',
                                'translate-x-6' => $metodo['habilitado'],
                                'translate-x-1' => ! $metodo['habilitado'],
                            ])></span>
                        </span>
                    </span>
                </button>
            @endforeach
        </div>
    </section>

    <section class="ui-card" aria-labelledby="transferencia-heading">
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                        <x-icons.arrows-right-left class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 id="transferencia-heading" class="text-lg font-bold text-[#14213d]">{{ __('Transferencia bancaria') }}</h2>
                        <p class="mt-1 max-w-2xl text-sm leading-5 text-slate-500">{{ __('Estos datos se mostrarán a los clientes que elijan este método de pago.') }}</p>
                    </div>
                </div>
            </div>
        </div>
        <form wire:submit="guardarDatosTransferencia" class="space-y-6 px-5 py-6 sm:px-6">
            <div>
                <h3 class="text-sm font-semibold text-[#14213d]">{{ __('Datos de la cuenta') }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ __('Completa todos los campos para que el cliente pueda identificar el pago.') }}</p>
            </div>
            <div class="grid gap-x-5 gap-y-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="transferenciaBanco" value="Banco" />
                    <x-text-input id="transferenciaBanco" wire:model="transferenciaBanco" type="text" class="mt-1.5 block w-full" placeholder="Banco Pichincha" />
                    @error('transferenciaBanco') <p class="ui-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-input-label for="transferenciaTipoCuenta" value="Tipo de cuenta" />
                    <x-text-input id="transferenciaTipoCuenta" wire:model="transferenciaTipoCuenta" type="text" class="mt-1.5 block w-full" placeholder="Cuenta de ahorros" />
                    @error('transferenciaTipoCuenta') <p class="ui-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-input-label for="transferenciaNumeroCuenta" value="Número de cuenta" />
                    <x-text-input id="transferenciaNumeroCuenta" wire:model="transferenciaNumeroCuenta" type="text" inputmode="numeric" class="mt-1.5 block w-full" placeholder="2204592986" maxlength="20" />
                    @error('transferenciaNumeroCuenta') <p class="ui-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-input-label for="transferenciaTitular" value="Titular de la cuenta" />
                    <x-text-input id="transferenciaTitular" wire:model="transferenciaTitular" type="text" class="mt-1.5 block w-full" placeholder="Jean Paul Mayorga" />
                    @error('transferenciaTitular') <p class="ui-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-input-label for="transferenciaCedulaTitular" value="Cédula del titular" />
                    <x-text-input id="transferenciaCedulaTitular" wire:model="transferenciaCedulaTitular" type="text" inputmode="numeric" class="mt-1.5 block w-full" placeholder="1805752685" maxlength="10" />
                    @error('transferenciaCedulaTitular') <p class="ui-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-input-label for="transferenciaWhatsapp" value="WhatsApp para comprobantes" />
                    <x-text-input id="transferenciaWhatsapp" wire:model="transferenciaWhatsapp" type="text" inputmode="tel" class="mt-1.5 block w-full" placeholder="593991234567" maxlength="15" />
                    @error('transferenciaWhatsapp') <p class="ui-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <button type="submit" class="ui-primary-button" wire:loading.attr="disabled" wire:loading.class="opacity-60">
                    <span wire:loading.remove wire:target="guardarDatosTransferencia">{{ __('Guardar datos bancarios') }}</span>
                    <span wire:loading wire:target="guardarDatosTransferencia">{{ __('Guardando...') }}</span>
                </button>
            </div>
        </form>
    </section>

    <section class="ui-card" aria-labelledby="avanzada-heading">
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                    <x-icons.cog-6-tooth class="h-5 w-5" />
                </span>
                <div>
                    <h2 id="avanzada-heading" class="text-lg font-bold text-[#14213d]">{{ __('Configuración avanzada') }}</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-5 text-slate-500">{{ __('Ajusta los parámetros globales del sistema y del módulo.') }}</p>
                </div>
            </div>
        </div>

        <div class="space-y-4 p-4 sm:p-5">
            @forelse($parametros as $modulo => $items)
                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <h3 class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">{{ $modulo }}</h3>
                    </div>
                    <div class="overflow-hidden rounded-lg border border-slate-200">
                        @foreach($items as $param)
                            @php
                                $isEditing = $editando === $param->modulo . '.' . $param->clave;
                                $decoded = json_decode($param->valor, true);
                                $displayValue = is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE) : ($decoded ?? $param->valor);
                                $etiquetaParametro = $etiquetasParametros[$param->clave] ?? str($param->clave)->headline();
                            @endphp
                            <div class="grid gap-2 border-b border-slate-100 px-3 py-2.5 last:border-b-0 md:grid-cols-[minmax(180px,1fr)_minmax(0,2fr)_auto] md:items-center md:gap-4">
                                <div class="min-w-0">
                                    <span class="block text-sm font-semibold text-[#14213d]">{{ $etiquetaParametro }}</span>
                                    <span class="sr-only">{{ $param->clave }}</span>
                                </div>
                                <div class="min-w-0">
                                    @if($isEditing)
                                        <form id="form-{{ $param->modulo }}-{{ $param->clave }}" wire:submit.prevent="guardar('{{ $param->modulo }}', '{{ $param->clave }}')" class="space-y-1">
                                            <label for="valor-{{ $param->modulo }}-{{ $param->clave }}" class="sr-only">{{ __('Nuevo valor para :clave', ['clave' => $param->clave]) }}</label>
                                             <input id="valor-{{ $param->modulo }}-{{ $param->clave }}" type="text" wire:model="valorEditando" class="ui-input w-full font-mono" placeholder="{{ __('Ingrese un valor JSON válido') }}">
                                             @error('valorEditando') <p class="ui-error">{{ $message }}</p> @enderror
                                        </form>
                                    @else
                                        <span class="block break-all font-mono text-xs leading-5 text-slate-600">{{ $displayValue }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center justify-end gap-2">
                                    @if($isEditing)
                                        <button type="submit" form="form-{{ $param->modulo }}-{{ $param->clave }}" wire:loading.attr="disabled" class="ui-primary-button min-h-9 px-3 py-1.5 text-xs">
                                            <span wire:loading.remove wire:target="guardar">{{ __('Guardar') }}</span>
                                            <span wire:loading wire:target="guardar">...</span>
                                        </button>
                                        <button type="button" wire:click="cancelarEdicion" class="ui-secondary-button min-h-9 px-3 py-1.5 text-xs">{{ __('Cancelar') }}</button>
                                    @else
                                        <button type="button" wire:click="iniciarEdicion('{{ $param->modulo }}', '{{ $param->clave }}')" class="ui-secondary-button min-h-9 px-3 py-1.5 text-xs">{{ __('Editar') }}</button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 px-5 py-12 text-center">
                    <p class="text-sm font-medium text-slate-500">{{ __('No hay parámetros de configuración disponibles.') }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ __('Los parámetros aparecerán aquí cuando estén disponibles.') }}</p>
                </div>
            @endforelse
        </div>
    </section>
</x-page-shell>
