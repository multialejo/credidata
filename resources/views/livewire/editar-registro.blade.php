<div class="max-w-7xl mx-auto space-y-6">

    {{-- Flash Alert Banner --}}
    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        {{-- Section Header --}}
        <div class="mb-6 border-b border-gray-100 pb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="text-xl font-bold text-gray-900">{{ __('Búsqueda y Edición de Registros') }}</h3>
                <p class="text-xs text-gray-500 mt-1">{{ __('Consulta registros por ID y actualiza información de contacto adicional.') }}</p>
            </div>

            @if($encontrado)
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded-lg text-xs font-semibold">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span>{{ __('Editando:') }} <strong class="font-mono">{{ $identificador }}</strong></span>
                </div>
            @endif
        </div>

        {{-- Search Input Form --}}
        <form wire:submit.prevent="buscar" class="mb-8">
            <div class="max-w-xl">
                <label for="identificador" class="block text-xs font-medium text-gray-700 mb-1">
                    {{ __('Identificador (Cédula o RUC)') }}
                </label>
                <div class="flex items-center gap-3">
                    <div class="relative flex-1">
                        <input type="text" id="identificador" wire:model="identificador"
                            placeholder="{{ __('Ej. 1713175071') }}"
                            @if($encontrado) disabled @endif
                            class="w-full border-gray-300 rounded-lg text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3 {{ $encontrado ? 'bg-gray-50 text-gray-500 cursor-not-allowed' : '' }}">
                    </div>

                    @if($encontrado)
                        <button type="button" wire:click="nuevaBusqueda"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors shrink-0">
                            {{ __('Cambiar') }}
                        </button>
                    @else
                        <button type="submit" wire:loading.attr="disabled" wire:target="buscar"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm shrink-0 flex items-center gap-2">
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
                    <p class="text-red-600 text-xs font-medium mt-1.5">{{ $message }}</p>
                @enderror
            </div>
        </form>

        {{-- Record Found: Contact Details Form --}}
        @if($encontrado)
            <div class="border-t border-gray-100 pt-6">
                <form wire:submit.prevent="guardar" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {{-- Telefones --}}
                        <div>
                            <label for="telefonos" class="block text-xs font-semibold text-gray-700 mb-1">
                                {{ __('Teléfonos') }}
                            </label>
                            <textarea id="telefonos" wire:model="telefonos" rows="4"
                                placeholder="{{ __('Ej. 0991234567&#10;022345678') }}"
                                class="w-full border-gray-300 rounded-lg text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"></textarea>
                            <p class="text-[11px] text-gray-400 mt-1">{{ __('Un teléfono por línea') }}</p>
                            @error('telefonos') <p class="text-red-600 text-xs font-medium mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Emails --}}
                        <div>
                            <label for="emails" class="block text-xs font-semibold text-gray-700 mb-1">
                                {{ __('Emails') }}
                            </label>
                            <textarea id="emails" wire:model="emails" rows="4"
                                placeholder="{{ __('Ej. contacto@ejemplo.com&#10;ventas@ejemplo.com') }}"
                                class="w-full border-gray-300 rounded-lg text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"></textarea>
                            <p class="text-[11px] text-gray-400 mt-1">{{ __('Un email por línea') }}</p>
                            @error('emails') <p class="text-red-600 text-xs font-medium mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Direcciones --}}
                        <div>
                            <label for="direcciones" class="block text-xs font-semibold text-gray-700 mb-1">
                                {{ __('Direcciones') }}
                            </label>
                            <textarea id="direcciones" wire:model="direcciones" rows="4"
                                placeholder="{{ __('Ej. Av. Amazonas N24-181&#10;Calle Calle 10 y Loja') }}"
                                class="w-full border-gray-300 rounded-lg text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"></textarea>
                            <p class="text-[11px] text-gray-400 mt-1">{{ __('Una dirección por línea') }}</p>
                            @error('direcciones') <p class="text-red-600 text-xs font-medium mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Actions & Legal Notice --}}
                    <div class="pt-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-2 text-xs text-amber-700 bg-amber-50 px-3 py-2 rounded-lg border border-amber-200">
                            <svg class="w-4 h-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ __('Solo se editan datos de contacto. La información oficial de fuentes (DINARDAP/SRI) se mantiene intacta.') }}</span>
                        </div>

                        <div class="flex items-center gap-3 shrink-0 justify-end">
                            <button type="button" wire:click="nuevaBusqueda"
                                class="px-4 py-2 text-gray-700 hover:text-gray-900 text-sm font-medium transition-colors">
                                {{ __('Cancelar') }}
                            </button>

                            <button type="submit" wire:loading.attr="disabled" wire:target="guardar"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm flex items-center gap-2">
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
            <div class="text-center py-12 border-2 border-dashed border-gray-100 rounded-xl">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <p class="text-gray-400 text-sm font-medium">{{ __('Ingrese una cédula o RUC para cargar y editar el registro.') }}</p>
            </div>
        @endif
    </div>
</div>
