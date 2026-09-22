<x-page-shell max-width="7xl">
    <x-page-header eyebrow="Administración" title="Búsqueda y edición de registros" description="Consulta registros por cédula o RUC y actualiza información de contacto adicional." />

    {{-- Flash Alert Banner --}}
    @if (session('status'))
        <div class="ui-alert ui-alert--success flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif

    <section class="ui-card p-5 sm:p-7">
        {{-- Section Header --}}
        <div class="mb-6 flex flex-col gap-4 border-b border-slate-100 pb-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="ui-section-title">{{ __('Buscar un registro') }}</h2>
            </div>

            @if($encontrado)
                <div class="ui-alert ui-alert--info inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold">
                    <svg class="h-4 w-4 text-[#3155d9]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span>{{ __('Editando:') }} <strong class="font-mono">{{ $identificador }}</strong></span>
                </div>
            @endif
        </div>

        {{-- Search Input Form --}}
        <form wire:submit.prevent="buscar" class="mb-8">
            <div class="max-w-xl">
                <label for="identificador" class="ui-label mb-1 text-xs">
                    {{ __('Identificador (Cédula o RUC)') }}
                </label>
                <div class="flex items-center gap-3">
                    <div class="relative flex-1">
                        <input type="text" id="identificador" wire:model="identificador"
                            placeholder="{{ __('Ej. 1713175071') }}"
                            @if($encontrado) disabled @endif
                            class="ui-input w-full font-mono {{ $encontrado ? 'bg-slate-50 text-slate-500' : '' }}">
                    </div>

                    @if($encontrado)
                        <button type="button" wire:click="nuevaBusqueda"
                            class="ui-secondary-button shrink-0">
                            {{ __('Cambiar') }}
                        </button>
                    @else
                        <button type="submit" wire:loading.attr="disabled" wire:target="buscar"
                            class="ui-primary-button shrink-0">
                            <span wire:loading.remove wire:target="buscar">{{ __('Buscar') }}</span>
                            <span wire:loading wire:target="buscar" class="flex items-center gap-1">
                                <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                {{ __('Buscando...') }}
                            </span>
                        </button>
                    @endif
                </div>
                @error('identificador')
                    <p class="ui-error">{{ $message }}</p>
                @enderror
            </div>
        </form>

        {{-- Record Found: Contact Details Form --}}
        @if($encontrado)
            <div class="border-t border-slate-100 pt-6">
                <form wire:submit.prevent="guardar" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {{-- Telefones --}}
                        <div>
                            <label for="telefonos" class="ui-label mb-1 text-xs">
                                {{ __('Teléfonos') }}
                            </label>
                            <textarea id="telefonos" wire:model="telefonos" rows="4"
                                placeholder="{{ __('Ej. 0991234567&#10;022345678') }}"
                                class="ui-input w-full font-mono"></textarea>
                            <p class="ui-help">{{ __('Un teléfono por línea') }}</p>
                            @error('telefonos') <p class="ui-error">{{ $message }}</p> @enderror
                        </div>

                        {{-- Emails --}}
                        <div>
                            <label for="emails" class="ui-label mb-1 text-xs">
                                {{ __('Emails') }}
                            </label>
                            <textarea id="emails" wire:model="emails" rows="4"
                                placeholder="{{ __('Ej. contacto@ejemplo.com&#10;ventas@ejemplo.com') }}"
                                class="ui-input w-full font-mono"></textarea>
                            <p class="ui-help">{{ __('Un email por línea') }}</p>
                            @error('emails') <p class="ui-error">{{ $message }}</p> @enderror
                        </div>

                        {{-- Direcciones --}}
                        <div>
                            <label for="direcciones" class="ui-label mb-1 text-xs">
                                {{ __('Direcciones') }}
                            </label>
                            <textarea id="direcciones" wire:model="direcciones" rows="4"
                                placeholder="{{ __('Ej. Av. Amazonas N24-181&#10;Calle Calle 10 y Loja') }}"
                                class="ui-input w-full"></textarea>
                            <p class="ui-help">{{ __('Una dirección por línea') }}</p>
                            @error('direcciones') <p class="ui-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Actions & Legal Notice --}}
                    <div class="flex flex-col justify-between gap-4 border-t border-slate-100 pt-4 sm:flex-row sm:items-center">
                        <div class="ui-alert ui-alert--warning flex items-center gap-2 text-xs">
                            <svg class="w-4 h-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ __('Solo se editan datos de contacto. La información oficial de fuentes (DINARDAP/SRI) se mantiene intacta.') }}</span>
                        </div>

                        <div class="flex items-center gap-3 shrink-0 justify-end">
                            <button type="button" wire:click="nuevaBusqueda"
                                class="ui-secondary-button">
                                {{ __('Cancelar') }}
                            </button>

                            <button type="submit" wire:loading.attr="disabled" wire:target="guardar"
                                class="ui-primary-button">
                                <span wire:loading.remove wire:target="guardar">{{ __('Guardar cambios') }}</span>
                                <span wire:loading wire:target="guardar" class="flex items-center gap-1">
                                    <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    {{ __('Guardando...') }}
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @else
            {{-- Empty State --}}
            <div class="rounded-xl border-2 border-dashed border-slate-200 px-5 py-12 text-center">
                <svg class="mx-auto mb-3 h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <p class="text-sm font-medium text-slate-500">{{ __('Ingresa una cédula o RUC para cargar y editar el registro.') }}</p>
            </div>
        @endif
    </section>
</x-page-shell>
