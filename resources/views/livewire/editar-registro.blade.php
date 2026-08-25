<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Búsqueda y Edición de Registros') }}</h3>

    {{-- Búsqueda por identificador --}}
    <form wire:submit="buscar" class="mb-6">
        <div class="flex items-end gap-3">
            <div class="flex-1">
                <label for="identificador" class="block text-xs text-gray-600 mb-1">
                    {{ __('Identificador (cédula o RUC)') }}
                </label>
                <input type="text" id="identificador" wire:model="identificador"
                    placeholder="{{ __('Ej. 1713175071') }}"
                    @disabled($encontrado)
                    class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono @disabled($encontrado) bg-gray-100 cursor-not-allowed">
                @error('identificador')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" @disabled($encontrado)
                class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 @disabled($encontrado) opacity-50 cursor-not-allowed">
                {{ __('Buscar') }}
            </button>
        </div>
    </form>

    @if($encontrado)
        {{-- Edición limitada a campos de contacto --}}
        <form wire:submit="guardar" class="space-y-4">
            <div>
                <label for="telefonos" class="block text-xs text-gray-600 mb-1">{{ __('Teléfonos') }}</label>
                <textarea id="telefonos" wire:model="telefonos" rows="3"
                    placeholder="{{ __('Un teléfono por línea') }}"
                    class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono"></textarea>
            </div>
            <div>
                <label for="emails" class="block text-xs text-gray-600 mb-1">{{ __('Emails') }}</label>
                <textarea id="emails" wire:model="emails" rows="3"
                    placeholder="{{ __('Un email por línea') }}"
                    class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono"></textarea>
            </div>
            <div>
                <label for="direcciones" class="block text-xs text-gray-600 mb-1">{{ __('Dirección') }}</label>
                <textarea id="direcciones" wire:model="direcciones" rows="3"
                    placeholder="{{ __('Una dirección por línea') }}"
                    class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ __('Guardar cambios') }}
                </button>
                <button type="button" wire:click="nuevaBusqueda"
                    class="px-4 py-2 bg-gray-200 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    {{ __('Nueva búsqueda') }}
                </button>
            </div>
        </form>

        <p class="mt-4 text-xs text-gray-500">
            {{ __('Solo se editan campos de contacto. Los datos de fuentes oficiales (Dinardap/SRI) no se modifican.') }}
        </p>
    @else
        <p class="text-center text-gray-500 py-8">{{ __('Ingrese un identificador para buscar el registro.') }}</p>
    @endif

    @if (session('status'))
        <p class="mt-4 text-sm text-green-600">{{ session('status') }}</p>
    @endif
</div>
