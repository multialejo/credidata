<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Editar Registro</h3>

    <div class="mb-4 flex gap-2">
        <input type="text" wire:model.live="identificador" placeholder="Cédula o RUC..."
            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm flex-1" />
        <button wire:click="buscar" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Buscar</button>
    </div>

    @if($documento)
        <div class="mt-4">
            <h4 class="text-md font-semibold text-gray-700 mb-2">Documento: {{ $identificador }}</h4>

            @if($guardado)
                <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-md">Cambios guardados correctamente.</div>
            @endif

            <div class="space-y-4">
                @foreach($camposEditables as $campo)
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ ucfirst($campo) }}</label>
                        <input type="text" wire:model.live="valores.{{ $campo }}"
                            class="mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full" />
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                <button wire:click="guardar" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Guardar cambios</button>
            </div>
        </div>
    @endif
</div>
