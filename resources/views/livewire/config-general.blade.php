<div class="max-w-7xl mx-auto space-y-6">

    {{-- Top Flash Alert --}}
    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- Main Container --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="mb-6 border-b border-gray-100 pb-4">
            <h3 class="text-xl font-bold text-gray-900">{{ __('Configuración General') }}</h3>
        </div>

        @forelse($parametros as $modulo => $items)
            <div class="mb-8 last:mb-0">
                {{-- Module Section Badge Header --}}
                <div class="flex items-center gap-2 mb-3">
                    <span class="px-2.5 py-1 text-xs font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 rounded-md">
                        {{ $modulo }}
                    </span>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto rounded-lg border border-gray-100">
                    <table class="w-full text-sm text-left divide-y divide-gray-200">
                        <thead class="bg-gray-50 text-gray-500 font-medium uppercase text-xs tracking-wider">
                            <tr>
                                <th class="py-3 px-4 w-1/4">{{ __('Clave') }}</th>
                                <th class="py-3 px-4 w-2/4">{{ __('Valor') }}</th>
                                <th class="py-3 px-4 w-1/6">{{ __('Actualizado') }}</th>
                                <th class="py-3 px-4 text-right w-1/12">{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach($items as $param)
                                @php
                                    $isEditing = $editando === $param->modulo . '.' . $param->clave;
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    {{-- Clave --}}
                                    <td class="py-3.5 px-4 align-middle">
                                        <code class="text-xs font-mono font-semibold text-gray-800 bg-gray-100 px-2 py-1 rounded">
                                            {{ $param->clave }}
                                        </code>
                                    </td>

                                    {{-- Valor (Inline Edit Mode) --}}
                                    <td class="py-3.5 px-4 align-middle">
                                        @if($isEditing)
                                            <form id="form-{{ $param->modulo }}-{{ $param->clave }}"
                                                  wire:submit.prevent="guardar('{{ $param->modulo }}', '{{ $param->clave }}')"
                                                  class="space-y-1">
                                                <input type="text"
                                                       wire:model="valorEditando"
                                                       class="w-full border-gray-300 rounded-lg text-sm font-mono focus:ring-indigo-500 focus:border-indigo-500 py-1.5 px-3"
                                                       placeholder="{{ __('Ingrese el nuevo valor') }}"
                                                       autofocus>
                                                @error('valorEditando')
                                                    <p class="text-red-600 text-xs font-medium">{{ $message }}</p>
                                                @enderror
                                            </form>
                                        @else
                                            <span class="font-mono text-gray-700 break-all text-xs">
                                                @php
                                                    $decoded = json_decode($param->valor, true);
                                                    $displayValue = is_array($decoded) ? json_encode($decoded) : ($decoded ?? $param->valor);
                                                @endphp
                                                {{ $displayValue }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Actualizado --}}
                                    <td class="py-3.5 px-4 text-gray-500 text-xs whitespace-nowrap align-middle">
                                        {{ $param->actualizado_en ? $param->actualizado_en->format('d/m/Y H:i') : '—' }}
                                    </td>

                                    {{-- Acciones --}}
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap align-middle">
                                        @if($isEditing)
                                            <div class="flex items-center justify-end gap-2">
                                                <button type="submit"
                                                        form="form-{{ $param->modulo }}-{{ $param->clave }}"
                                                        wire:loading.attr="disabled"
                                                        class="px-3 py-1 bg-indigo-600 text-white rounded-lg text-xs font-medium hover:bg-indigo-700 transition-colors shadow-sm disabled:opacity-50">
                                                    <span wire:loading.remove wire:target="guardar">{{ __('Guardar') }}</span>
                                                    <span wire:loading wire:target="guardar">...</span>
                                                </button>
                                                <button type="button"
                                                        wire:click="cancelarEdicion"
                                                        class="px-2.5 py-1 text-gray-500 hover:text-gray-700 text-xs font-medium transition-colors">
                                                    {{ __('Cancelar') }}
                                                </button>
                                            </div>
                                        @else
                                            <button wire:click="iniciarEdicion('{{ $param->modulo }}', '{{ $param->clave }}')"
                                                    class="px-3 py-1 border border-gray-200 text-gray-700 hover:bg-gray-50 rounded-lg text-xs font-medium transition-colors">
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
            <div class="text-center py-12 border-2 border-dashed border-gray-100 rounded-lg">
                <p class="text-gray-400 text-sm">{{ __('No hay parámetros de configuración disponibles.') }}</p>
            </div>
        @endforelse
    </div>
</div>
