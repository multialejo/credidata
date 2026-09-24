<x-page-shell max-width="5xl">
<x-page-header eyebrow="Desarrolladores" title="API Key" description="Administra las credenciales que usan tus integraciones para consultar Credidata." />
@if(session('status'))
    <x-alert variant="success" class="mb-6">{{ session('status') }}</x-alert>
@endif
<section class="ui-card divide-y divide-slate-100 p-5 sm:p-7">

    <!-- Header & Alert Block -->
    <div class="pb-6">
        @if($nuevaKey)
            <div class="ui-alert ui-alert--warning mb-6" x-data="{ copied: false }">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p class="flex items-center gap-1.5 text-sm font-semibold">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        ¡Nueva API Key generada!
                    </p>
                    <span class="text-xs font-medium">Cópiala ahora, no volverá a mostrarse.</span>
                </div>

                <div class="mt-2 flex items-center gap-2">
                    <code class="flex-1 break-all rounded-lg border border-amber-200 bg-amber-100/70 p-2.5 font-mono text-sm text-amber-950 select-all" id="new-key">
                        {{ $nuevaKey }}
                    </code>
                    <button
                        type="button"
                        x-on:click="navigator.clipboard.writeText('{{ $nuevaKey }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="ui-secondary-button min-h-10 shrink-0 border-amber-300 bg-amber-100 px-3 py-2 text-xs text-amber-900 hover:bg-amber-200">
                        <span x-text="copied ? '¡Copiado!' : 'Copiar'"></span>
                    </button>
                </div>
            </div>
        @endif

        <!-- Metadata Summary Grid -->
        <dl class="grid grid-cols-2 gap-x-4 gap-y-5 text-sm sm:grid-cols-3">
            <div>
                <dt class="ui-eyebrow">Alias</dt>
                <dd class="mt-1 font-medium text-[#14213d]">{{ $alias ?: '—' }}</dd>
            </div>
            <div>
                <dt class="ui-eyebrow">Prefijo</dt>
                <dd class="mt-1 w-fit rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold text-slate-700">{{ $prefijo ?? '—' }}</dd>
            </div>
            <div>
                <dt class="ui-eyebrow">Estado</dt>
                <dd class="mt-1">
                    @if($revocada)
                        <span class="ui-table-badge border border-rose-200 bg-rose-100 text-rose-800">
                            <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-rose-500"></span> Revocada
                        </span>
                    @else
                        <span class="ui-table-badge border border-emerald-200 bg-emerald-100 text-emerald-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span> Activa
                        </span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="ui-eyebrow">Creada</dt>
                <dd class="mt-1 text-slate-700">{{ $creada ? $creada->format('d/m/Y H:i') : '—' }}</dd>
            </div>
            <div>
                <dt class="ui-eyebrow">Último uso</dt>
                <dd class="mt-1 text-slate-700">{{ $ultimoUso ? $ultimoUso->format('d/m/Y H:i') : 'Nunca' }}</dd>
            </div>
            <div>
                <dt class="ui-eyebrow">Rotación sugerida</dt>
                <dd class="mt-1 text-slate-700">{{ $rotacionSugerida ? $rotacionSugerida->format('d/m/Y') : '—' }}</dd>
            </div>
        </dl>

        @if($alcance && count($alcance) > 0)
            <div class="mt-5">
                <dt class="ui-eyebrow mb-2">Permisos activos</dt>
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
    <form wire:submit.prevent="guardarConfiguracion" class="py-6 space-y-6">
        <div>
            <h2 class="ui-section-title">Configuración de la API Key</h2>
            <p class="mt-1 text-sm leading-6 text-slate-600">Define dónde puede usarse esta clave y qué consultas puede realizar.</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="key-alias" class="ui-label">Alias de la clave</label>
                <input id="key-alias" type="text" wire:model="alias"
                    class="ui-input mt-1 w-full"
                    placeholder="Ej. Integración Facturación" maxlength="100">
                @error('alias') <span class="ui-error block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="key-ips" class="ui-label">IPs permitidas <span class="text-xs font-normal text-slate-500">(una por línea)</span></label>
                <textarea id="key-ips" wire:model="ips" rows="3"
                    class="ui-input mt-1 w-full font-mono"
                    placeholder="192.168.1.1&#10;10.0.0.1"></textarea>
                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2">
                    <p class="ui-help mt-0">Tu IP actual: <code class="rounded bg-slate-100 px-1 font-mono text-slate-800">{{ $ipDetectada ?: 'desconocida' }}</code></p>
                </div>
                <p class="ui-help">Si no agregas IPs, la clave podrá usarse desde cualquier dirección.</p>
                @error('ips') <span class="ui-error block">{{ $message }}</span> @enderror
            </div>
        </div>

        <fieldset>
            <legend class="ui-label mb-1">Permisos de consulta</legend>
            <p class="mb-3 text-sm text-slate-600">Elige consultas específicas o acceso completo. El acceso completo incluye todas las consultas actuales y futuras.</p>
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach([
                    'consulta:cedula' => ['label' => 'Cédula', 'description' => 'Consultar datos de personas.'],
                    'consulta:ruc' => ['label' => 'RUC', 'description' => 'Consultar datos de empresas.'],
                    'consulta:*' => ['label' => 'Acceso completo', 'description' => 'Incluye consultas actuales y futuras.'],
                ] as $scope => $permission)
                    <label class="inline-flex min-h-11 cursor-pointer items-start gap-2 rounded-xl border border-slate-200 p-3 text-sm text-slate-700 has-[:checked]:border-[#3155d9] has-[:checked]:bg-[#e8edf9]">
                        <input type="checkbox" wire:model="scopes" value="{{ $scope }}"
                            @disabled($scope !== 'consulta:*' && in_array('consulta:*', $scopes, true))
                            class="mt-0.5 rounded border-slate-300 text-[#3155d9] focus:ring-[#3155d9] disabled:cursor-not-allowed disabled:opacity-50">
                        <span>
                            <span class="block font-semibold">{{ $permission['label'] }}</span>
                            <span class="mt-0.5 block text-xs leading-5 text-slate-600">{{ $permission['description'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('scopes') <span class="ui-error block">{{ $message }}</span> @enderror
        </fieldset>

        <div class="flex justify-end pt-2">
            <button type="submit" wire:loading.attr="disabled"
                class="ui-primary-button">
                <span wire:loading.remove wire:target="guardarConfiguracion">Guardar cambios</span>
                <span wire:loading wire:target="guardarConfiguracion">Guardando...</span>
            </button>
        </div>
    </form>

    <!-- Key Lifecycle Actions -->
    <section class="mt-2 border-t border-slate-200 pt-6" aria-labelledby="key-lifecycle-title">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="max-w-xl">
                <h2 id="key-lifecycle-title" class="ui-section-title">Ciclo de vida de la API Key</h2>
                @if($revocada)
                    <p class="mt-1 text-sm leading-6 text-slate-600">La clave está revocada y no puede realizar consultas. Genera una nueva para restablecer el acceso con la configuración guardada.</p>
                @else
                    <p class="mt-1 text-sm leading-6 text-slate-600">Regenerar invalida inmediatamente la clave actual. Revocar desactiva el acceso sin crear una clave nueva.</p>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-3 sm:justify-end">
                @if($revocada)
                    <button type="button" wire:click="generar" wire:loading.attr="disabled" class="ui-secondary-button">
                        <span wire:loading.remove wire:target="generar">Generar nueva API Key</span>
                        <span wire:loading wire:target="generar">Generando...</span>
                    </button>
                @else
                    <button type="button" wire:click="generar" wire:confirm="¿Generar una nueva API Key? La anterior se desactivará inmediatamente." wire:loading.attr="disabled"
                        class="ui-secondary-button">
                        <span wire:loading.remove wire:target="generar">Regenerar clave</span>
                        <span wire:loading wire:target="generar">Regenerando...</span>
                    </button>
                    <button type="button" wire:click="revocar" wire:confirm="¿Estás seguro de revocar esta API Key? Esta acción deshabilitará el acceso de forma permanente." wire:loading.attr="disabled"
                        class="ui-danger-button">
                        <span wire:loading.remove wire:target="revocar">Revocar clave</span>
                        <span wire:loading wire:target="revocar">Revocando...</span>
                    </button>
                @endif
            </div>
        </div>
    </section>
</section>
</x-page-shell>
