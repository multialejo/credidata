<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Configuración General</h3>

    @if(session()->has('config-error'))
        <div class="mb-4 p-4 bg-red-50 text-red-700 rounded-md">{{ session('config-error') }}</div>
    @endif

    @foreach($parametros as $modulo => $items)
        <div class="mb-6">
            <h4 class="text-md font-semibold text-gray-700 mb-2">{{ ucfirst($modulo) }}</h4>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clave</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($items as $item)
                        @php $key = "{$modulo}.{$item->clave}"; @endphp
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->clave }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                @if($editando[$key])
                                    <input type="text" wire:model.live="valores.{{ $key }}"
                                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full" />
                                @else
                                    {{ $item->valor }}
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($editando[$key])
                                    <button wire:click="guardar('{{ $modulo }}', '{{ $item->clave }}')" class="text-green-600 hover:text-green-900 mr-2">Guardar</button>
                                    <button wire:click="toggleEditar('{{ $modulo }}', '{{ $item->clave }}')" class="text-gray-600 hover:text-gray-900">Cancelar</button>
                                @else
                                    <button wire:click="toggleEditar('{{ $modulo }}', '{{ $item->clave }}')" class="text-indigo-600 hover:text-indigo-900">Editar</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>
