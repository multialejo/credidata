<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 divide-y divide-gray-100">

    <!-- Header & Alert Block -->
    <div class="pb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">API Key</h3>

        @if($nuevaKey)
            <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg shadow-sm" x-data="{ copied: false }">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-amber-900 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        ¡Nueva API Key generada!
                    </p>
                    <span class="text-xs text-amber-700 font-medium">Cópiala ahora, no volverá a mostrarse.</span>
                </div>

                <div class="mt-2 flex items-center gap-2">
                    <code class="flex-1 font-mono text-sm break-all bg-amber-100/70 text-amber-950 p-2.5 rounded border border-amber-200 select-all" id="new-key">
                        {{ $nuevaKey }}
                    </code>
                    <button
                        type="button"
                        x-on:click="navigator.clipboard.writeText('{{ $nuevaKey }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="px-3 py-2 text-xs font-medium text-amber-900 bg-amber-200 hover:bg-amber-300 rounded-md transition-colors flex items-center shrink-0">
                        <span x-text="copied ? '¡Copiado!' : 'Copiar'"></span>
                    </button>
                </div>
            </div>
        @endif

        <!-- Metadata Summary Grid -->
        <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-5 text-sm">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Alias</dt>
                <dd class="mt-1 font-medium text-gray-900">{{ $alias ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Prefijo</dt>
                <dd class="mt-1 font-mono text-xs font-semibold bg-gray-100 text-gray-700 px-2 py-0.5 rounded w-fit">{{ $prefijo ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Estado</dt>
                <dd class="mt-1">
                    @if($revocada)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span> Revocada
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span> Activa
                        </span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Creada</dt>
                <dd class="mt-1 text-gray-700">{{ $creada ? $creada->format('d/m/Y H:i') : '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Último uso</dt>
                <dd class="mt-1 text-gray-700">{{ $ultimoUso ? $ultimoUso->format('d/m/Y H:i') : 'Nunca' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Rotación sugerida</dt>
                <dd class="mt-1 text-gray-700">{{ $rotacionSugerida ? $rotacionSugerida->format('d/m/Y') : '—' }}</dd>
            </div>
        </dl>

        @if($alcance && count($alcance) > 0)
            <div class="mt-5">
                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Permisos Activos</dt>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($alcance as $permiso)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-slate-100 text-slate-700 border border-slate-200">
                            {{ $permiso }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Configuration Form Section -->
    <form wire:submit.prevent="guardarConfiguracion" class="py-6 space-y-5">
        <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Configuración</h4>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="key-alias" class="block text-sm font-medium text-gray-700">Alias de la clave</label>
                <input id="key-alias" type="text" wire:model="alias"
                    class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    placeholder="Ej. Integración Facturación" maxlength="100">
                @error('alias') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="key-ips" class="block text-sm font-medium text-gray-700">IPs permitidas <span class="text-xs font-normal text-gray-500">(una por línea)</span></label>
                <textarea id="key-ips" wire:model="ips" rows="3"
                    class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono"
                    placeholder="192.168.1.1&#10;10.0.0.1"></textarea>
                <p class="mt-1 text-xs text-gray-500">
                    Tu IP actual detectada: <code class="font-mono bg-gray-100 px-1 rounded text-gray-800">{{ $ipDetectada ?: 'desconocida' }}</code>
                </p>
                @error('ips') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <fieldset>
            <legend class="text-sm font-medium text-gray-700 mb-2">Scopes (Permisos)</legend>
            <div class="flex flex-wrap gap-4 bg-gray-50 p-3 rounded-md border border-gray-200">
                @foreach(['consulta:cedula', 'consulta:ruc', 'consulta:*'] as $scope)
                    <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                        <input type="checkbox" wire:model="scopes" value="{{ $scope }}"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="font-mono text-xs">{{ $scope }}</span>
                    </label>
                @endforeach
            </div>
            @error('scopes') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
        </fieldset>

        <div class="flex justify-end pt-2">
            <button type="submit" wire:loading.attr="disabled"
                class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 disabled:opacity-50 transition-colors">
                <span wire:loading.remove wire:target="guardarConfiguracion">Guardar cambios</span>
                <span wire:loading wire:target="guardarConfiguracion">Guardando...</span>
            </button>
        </div>
    </form>

    <!-- Key Actions Footer -->
    <div class="pt-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs text-gray-500">Gestión del ciclo de vida de la API Key</p>
        </div>

        <div class="flex items-center gap-3">
            @if($revocada)
                <button type="button" wire:click="generar" wire:loading.attr="disabled"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition-colors">
                    Generar nueva API Key
                </button>
            @else
                <button type="button" wire:click="generar" wire:confirm="¿Generar una nueva API Key? La anterior se desactivará inmediatamente." wire:loading.attr="disabled"
                    class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition-colors">
                    Regenerar
                </button>
                <button type="button" wire:click="revocar" wire:confirm="¿Estás seguro de revocar esta API Key? Esta acción deshabilitará el acceso de forma permanente." wire:loading.attr="disabled"
                    class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 transition-colors">
                    Revocar
                </button>
            @endif
        </div>
    </div>
</div>
