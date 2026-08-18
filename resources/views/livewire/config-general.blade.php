<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Configuración General') }}</h3>

    @forelse($parametros as $modulo => $items)
        <div class="mb-6">
            <h4 class="text-md font-medium text-gray-700 mb-2 uppercase tracking-wide">{{ $modulo }}</h4>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left">
                            <th class="py-2 pr-4 font-medium text-gray-600">{{ __('Clave') }}</th>
                            <th class="py-2 pr-4 font-medium text-gray-600">{{ __('Valor') }}</th>
                            <th class="py-2 pr-4 font-medium text-gray-600">{{ __('Actualizado') }}</th>
                            <th class="py-2 font-medium text-gray-600">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $param)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-2 pr-4">
                                    <span class="font-mono">{{ $param->clave }}</span>
                                </td>
                                <td class="py-2 pr-4">
                                    @if($editando === $param->modulo . '.' . $param->clave)
                                        <form wire:submit="guardar('{{ $param->modulo }}', '{{ $param->clave }}')">
                                            <input type="text" wire:model="valorEditando"
                                                class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono">
                                            @error('valorEditando')
                                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                            @enderror
                                        </form>
                                    @else
                                        <span class="font-mono">{{ json_decode($param->valor) }}</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-4 text-gray-700 whitespace-nowrap">
                                    {{ $param->actualizado_en ? $param->actualizado_en->format('d/m/Y H:i') : '—' }}
                                </td>
                                <td class="py-2">
                                    @if($editando === $param->modulo . '.' . $param->clave)
                                        <div class="flex items-center gap-3">
                                            <button type="submit" form="form-{{ $param->modulo }}-{{ $param->clave }}"
                                                class="text-green-600 hover:text-green-800 text-sm font-medium">
                                                {{ __('Guardar') }}
                                            </button>
                                            <button type="button" wire:click="cancelarEdicion"
                                                class="text-gray-500 hover:text-gray-700 text-sm">
                                                {{ __('Cancelar') }}
                                            </button>
                                        </div>
                                    @else
                                        <button wire:click="iniciarEdicion('{{ $param->modulo }}', '{{ $param->clave }}')"
                                            class="text-indigo-600 hover:text-indigo-800 text-sm">
                                            {{ __('Editar') }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <p class="text-center text-gray-500 py-8">{{ __('No hay parámetros de configuración.') }}</p>
    @endforelse

    @if (session('status'))
        <p class="mt-4 text-sm text-green-600">{{ session('status') }}</p>
    @endif
</div>