<x-page-shell max-width="6xl">
    <x-page-header eyebrow="APIKEYS" title="Gestión de Credenciales" description="Crea una credencial independiente para cada sistema que conectes con Credidata." />

    @if (session('status'))
        <x-alert variant="success">{{ session('status') }}</x-alert>
    @endif

    @if ($nuevaKey)
        <section class="rounded-2xl border border-amber-300 bg-amber-50 p-5 sm:p-6" role="status" aria-labelledby="new-api-key-title" x-data="{ copied: false }">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 id="new-api-key-title" class="text-lg font-semibold text-[#14213d]">Guardá la clave de {{ $nombreNuevaKey }}</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-700">Este es el único momento en que vas a poder verla. Guardala en un lugar seguro y actualizá la configuración del sistema antes de descartar la clave anterior.</p>
                </div>
                <button type="button" x-on:click="navigator.clipboard.writeText(@js($nuevaKey)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })" x-bind:title="copied ? 'Copiada' : 'Copiar clave'" x-bind:aria-label="copied ? 'Clave copiada' : 'Copiar clave'" class="group relative inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center rounded-xl border border-amber-400 bg-white text-[#14213d] transition hover:bg-amber-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#3155d9]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 8.75V5.5A1.75 1.75 0 0 1 9.75 3.75h8.5A1.75 1.75 0 0 1 20 5.5v10.25a1.75 1.75 0 0 1-1.75 1.75H15M5.75 8.75h7.5A1.75 1.75 0 0 1 15 10.5v9a1.75 1.75 0 0 1-1.75 1.75h-7.5A1.75 1.75 0 0 1 4 19.5v-9a1.75 1.75 0 0 1 1.75-1.75Z" />
                    </svg>
                    <span role="tooltip" x-text="copied ? 'Copiada' : 'Copiar clave'" class="pointer-events-none absolute right-0 top-full z-10 mt-2 whitespace-nowrap rounded-lg bg-[#14213d] px-2.5 py-1.5 text-xs font-medium text-white opacity-0 shadow-sm transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100"></span>
                </button>
                <button type="button" wire:click="ocultarNuevaKey" class="min-h-11 text-sm font-semibold text-slate-700 underline underline-offset-4 focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3155d9]">Ocultar</button>
            </div>
            <code class="mt-4 block rounded-xl border border-amber-200 bg-white p-3 font-mono text-sm leading-6 text-[#14213d] [overflow-wrap:anywhere]">{{ $nuevaKey }}</code>
        </section>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-[#14213d]">Tus credenciales</h2>
            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">Usa una credencial por aplicación. Para colaborar, habilita la opción "Aportes" o genera una nueva clave dedicada.</p>
        </div>
        @if (! $mostrarFormulario)
            <button type="button" wire:click="abrirFormulario" class="ui-primary-button min-h-11 shrink-0 px-4 py-2">
                Crear API Key
            </button>
        @endif
    </div>

    @if ($mostrarFormulario)
        <section class="ui-card p-5 sm:p-7" aria-labelledby="api-key-form-title">
            <div class="flex flex-col gap-1">
                <h2 id="api-key-form-title" class="ui-section-title">{{ $editandoId ? 'Configurar API Key' : 'Nueva API Key' }}</h2>
                <p class="text-sm leading-6 text-slate-600">{{ $editandoId ? 'Los cambios aplican únicamente a esta clave.' : 'Asigna un nombre que te permita reconocer el sistema que la va a usar.' }}</p>
            </div>

            <form wire:submit.prevent="{{ $editandoId ? 'guardar' : 'crear' }}" class="mt-6 space-y-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="api-key-name" class="ui-label">Nombre del aplicativo</label>
                        <input id="api-key-name" type="text" wire:model="nombre" class="ui-input mt-1 w-full" placeholder="Ej. Sistema de facturación" maxlength="100" required>
                        @error('nombre') <span class="ui-error mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="api-key-ips" class="ui-label">IPs permitidas <span class="font-normal text-slate-500">(una por línea)</span></label>
                        <textarea id="api-key-ips" wire:model="ips" rows="3" class="ui-input mt-1 w-full font-mono" placeholder="192.0.2.10&#10;198.51.100.24"></textarea>
                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2">
                            <p class="ui-help mt-0">Tu IP actual: <code class="rounded bg-slate-100 px-1 font-mono text-slate-800">{{ $ipDetectada ?: 'desconocida' }}</code></p>
                            @if ($ipDetectada)
                                <button type="button" wire:click="agregarIpActual" class="min-h-11 text-sm font-semibold text-[#3155d9] underline decoration-transparent underline-offset-4 hover:decoration-current focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3155d9]">Agregar IP actual</button>
                            @endif
                        </div>
                        <p class="ui-help">Si no indicás IPs, esta clave podrá usarse desde cualquier dirección.</p>
                        @error('ips') <span class="ui-error mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <fieldset>
                    <legend class="ui-label">Permisos de consulta</legend>
                    <p class="mb-3 mt-1 text-sm leading-6 text-slate-600">El acceso completo incluye las consultas actuales y futuras.</p>
                    <div class="grid gap-3 sm:grid-cols-3">
                        @foreach ([
                            'consulta:cedula' => ['label' => 'Cédula', 'description' => 'Consultar datos de personas.'],
                            'consulta:ruc' => ['label' => 'RUC', 'description' => 'Consultar datos de empresas.'],
                            'consulta:*' => ['label' => 'Todas las consultas', 'description' => 'Incluye consultas actuales y futuras.'],
                        ] as $scope => $permission)
                            <label class="flex min-h-12 cursor-pointer items-start gap-3 rounded-xl border border-slate-200 px-3 py-3 text-sm text-slate-700 transition-colors has-[:checked]:border-[#3155d9] has-[:checked]:bg-[#e8edf9]">
                                <input type="checkbox" wire:model="scopes" value="{{ $scope }}" @disabled($scope !== 'consulta:*' && in_array('consulta:*', $scopes, true)) class="mt-0.5 rounded border-slate-300 text-[#3155d9] focus:ring-[#3155d9] disabled:cursor-not-allowed disabled:opacity-50">
                                <span>
                                    <span class="block font-semibold">{{ $permission['label'] }}</span>
                                    <span class="mt-1 block text-xs leading-5 text-slate-600">{{ $permission['description'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('scopes') <span class="ui-error mt-2 block">{{ $message }}</span> @enderror
                </fieldset>

                <fieldset>
                    <legend class="ui-label">Permisos de colaboración</legend>
                    <p class="mb-3 mt-1 text-sm leading-6 text-slate-600">Para reducir el alcance de una integración, podés crear una clave dedicada solo a colaborar.</p>
                    <label class="flex min-h-12 max-w-2xl cursor-pointer items-start gap-3 rounded-xl border border-slate-200 px-3 py-3 text-sm text-slate-700 transition-colors has-[:checked]:border-[#3155d9] has-[:checked]:bg-[#e8edf9]">
                        <input type="checkbox" wire:model="scopes" value="colaboradores:aportes" class="mt-0.5 rounded border-slate-300 text-[#3155d9] focus:ring-[#3155d9]">
                        <span>
                            <span class="block font-semibold">Aportes</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-600">Enviar y consultar aportes de datos del perfil colaborador.</span>
                        </span>
                    </label>
                    @error('scopes.*') <span class="ui-error mt-2 block">{{ $message }}</span> @enderror
                </fieldset>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelarEdicion" class="ui-secondary-button min-h-11 px-4 py-2">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" class="ui-primary-button min-h-11 px-4 py-2">
                        <span wire:loading.remove>{{ $editandoId ? 'Guardar cambios' : 'Crear clave' }}</span>
                        <span wire:loading>{{ $editandoId ? 'Guardando…' : 'Creando…' }}</span>
                    </button>
                </div>
            </form>
        </section>
    @endif

    <section class="ui-card overflow-hidden" aria-labelledby="api-keys-list-title">
        <h2 id="api-keys-list-title" class="sr-only">API Keys registradas</h2>
        <div wire:loading.flex role="status" class="items-center gap-2 border-b border-slate-200 px-4 py-3 text-sm text-slate-600">
            <span class="h-2 w-2 animate-pulse rounded-full bg-[#3155d9]"></span>
            Actualizando las API Keys…
        </div>
        @if ($apiKeys->isEmpty())
            <div class="px-5 py-12 text-center sm:px-8">
                <h3 class="text-base font-semibold text-[#14213d]">Todavía no hay API Keys</h3>
                <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-600">Creá una clave por cada sistema que quieras integrar. El secreto se muestra una sola vez.</p>
                @if (! $mostrarFormulario)
                    <button type="button" wire:click="abrirFormulario" class="ui-primary-button mt-5 min-h-11 px-4 py-2">Crear primera API Key</button>
                @endif
            </div>
        @else
            <div class="divide-y divide-slate-200 sm:hidden">
                @foreach ($apiKeys as $apiKey)
                    <article wire:key="api-key-mobile-{{ $apiKey->id }}" class="space-y-4 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="break-words font-semibold text-[#14213d]">{{ $apiKey->nombre }}</h3>
                                <p class="mt-1 text-xs text-slate-500">Creada {{ $apiKey->creada_en?->format('d/m/Y') ?? '—' }}</p>
                            </div>
                            @if ($apiKey->revocada)
                                <span class="ui-table-badge shrink-0 border border-rose-200 bg-rose-100 text-rose-800">Revocada</span>
                            @else
                                <span class="ui-table-badge shrink-0 border border-emerald-200 bg-emerald-100 text-emerald-800">Activa</span>
                            @endif
                        </div>
                        <dl class="grid grid-cols-2 gap-x-3 gap-y-3 text-sm">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500">Prefijo</dt>
                                <dd class="mt-1 break-all font-mono text-xs text-slate-700">cd_sk_{{ $apiKey->prefijo }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500">Último uso</dt>
                                <dd class="mt-1 text-slate-700">{{ $apiKey->ultimo_uso_en?->format('d/m/Y H:i') ?? 'Nunca' }}</dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500">Permisos</dt>
                                <dd class="mt-1 break-words text-slate-700">{{ implode(', ', array_intersect($apiKey->alcance ?? [], \App\Services\ApiKeyService::SCOPES)) ?: 'Sin permisos' }}</dd>
                            </div>
                        </dl>
                        <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-3">
                            <button type="button" wire:click="editar({{ $apiKey->id }})" wire:loading.attr="disabled" title="Configurar" aria-label="Configurar {{ $apiKey->nombre }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl text-slate-600 transition hover:bg-slate-100 hover:text-[#3155d9] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#3155d9] disabled:cursor-not-allowed disabled:opacity-50">
                                <x-icons.cog-6-tooth class="h-5 w-5" />
                            </button>
                            <button type="button" wire:click="rotar({{ $apiKey->id }})" wire:confirm="{{ $apiKey->revocada ? '¿Generar una clave nueva para ' . $apiKey->nombre . '?' : '¿Rotar la clave de ' . $apiKey->nombre . '? La clave actual dejará de funcionar inmediatamente.' }}" wire:loading.attr="disabled" title="{{ $apiKey->revocada ? 'Generar nueva' : 'Rotar' }}" aria-label="{{ $apiKey->revocada ? 'Generar una clave nueva para ' : 'Rotar la clave de ' }}{{ $apiKey->nombre }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl text-slate-600 transition hover:bg-slate-100 hover:text-[#3155d9] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#3155d9] disabled:cursor-not-allowed disabled:opacity-50">
                                <x-icons.arrow-path wire:loading.remove wire:target="rotar({{ $apiKey->id }})" class="h-5 w-5" />
                                <svg wire:loading wire:target="rotar({{ $apiKey->id }})" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                            </button>
                            @unless ($apiKey->revocada)
                                <button type="button" wire:click="revocar({{ $apiKey->id }})" wire:confirm="¿Revocar la clave de {{ $apiKey->nombre }}? El sistema dejará de tener acceso." wire:loading.attr="disabled" title="Revocar" aria-label="Revocar {{ $apiKey->nombre }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl text-rose-700 transition hover:bg-rose-50 hover:text-rose-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-700 disabled:cursor-not-allowed disabled:opacity-50">
                                    <x-icons.x-mark wire:loading.remove wire:target="revocar({{ $apiKey->id }})" class="h-5 w-5" />
                                    <svg wire:loading wire:target="revocar({{ $apiKey->id }})" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                                </button>
                            @endunless
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="hidden overflow-x-auto sm:block">
                <table class="ui-data-table__table w-full min-w-[760px]">
                    <caption class="sr-only">Claves de API del cliente, su estado y permisos asignados</caption>
                    <thead>
                        <tr>
                            <th scope="col">Aplicativo</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Prefijo</th>
                            <th scope="col">Último uso</th>
                            <th scope="col">Permisos</th>
                            <th scope="col"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($apiKeys as $apiKey)
                            <tr wire:key="api-key-{{ $apiKey->id }}">
                                <th scope="row" class="min-w-44 text-left font-semibold text-[#14213d]">
                                    {{ $apiKey->nombre }}
                                    <span class="mt-1 block font-normal text-slate-500">Creada {{ $apiKey->creada_en?->format('d/m/Y') ?? '—' }}</span>
                                </th>
                                <td>
                                    @if ($apiKey->revocada)
                                        <span class="ui-table-badge border border-rose-200 bg-rose-100 text-rose-800">Revocada</span>
                                    @else
                                        <span class="ui-table-badge border border-emerald-200 bg-emerald-100 text-emerald-800">Activa</span>
                                    @endif
                                </td>
                                <td><code class="font-mono text-xs text-slate-700">cd_sk_{{ $apiKey->prefijo }}</code></td>
                                <td class="whitespace-nowrap text-sm text-slate-600">{{ $apiKey->ultimo_uso_en?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
                                <td class="max-w-56 text-xs leading-5 text-slate-600">{{ implode(', ', array_intersect($apiKey->alcance ?? [], \App\Services\ApiKeyService::SCOPES)) ?: 'Sin permisos' }}</td>
                                <td>
                                    <div class="flex min-w-40 flex-wrap justify-end gap-2">
                                        <button type="button" wire:click="editar({{ $apiKey->id }})" wire:loading.attr="disabled" title="Configurar" aria-label="Configurar {{ $apiKey->nombre }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl text-slate-600 transition hover:bg-slate-100 hover:text-[#3155d9] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#3155d9] disabled:cursor-not-allowed disabled:opacity-50">
                                            <x-icons.cog-6-tooth class="h-5 w-5" />
                                        </button>
                                        <button type="button" wire:click="rotar({{ $apiKey->id }})" wire:confirm="{{ $apiKey->revocada ? '¿Generar una clave nueva para ' . $apiKey->nombre . '?' : '¿Rotar la clave de ' . $apiKey->nombre . '? La clave actual dejará de funcionar inmediatamente.' }}" wire:loading.attr="disabled" title="{{ $apiKey->revocada ? 'Generar nueva' : 'Rotar' }}" aria-label="{{ $apiKey->revocada ? 'Generar una clave nueva para ' : 'Rotar la clave de ' }}{{ $apiKey->nombre }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl text-slate-600 transition hover:bg-slate-100 hover:text-[#3155d9] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#3155d9] disabled:cursor-not-allowed disabled:opacity-50">
                                            <x-icons.arrow-path wire:loading.remove wire:target="rotar({{ $apiKey->id }})" class="h-5 w-5" />
                                            <svg wire:loading wire:target="rotar({{ $apiKey->id }})" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                                        </button>
                                        @unless ($apiKey->revocada)
                                            <button type="button" wire:click="revocar({{ $apiKey->id }})" wire:confirm="¿Revocar la clave de {{ $apiKey->nombre }}? El sistema dejará de tener acceso." wire:loading.attr="disabled" title="Revocar" aria-label="Revocar {{ $apiKey->nombre }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl text-rose-700 transition hover:bg-rose-50 hover:text-rose-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-700 disabled:cursor-not-allowed disabled:opacity-50">
                                                <x-icons.x-mark wire:loading.remove wire:target="revocar({{ $apiKey->id }})" class="h-5 w-5" />
                                                <svg wire:loading wire:target="revocar({{ $apiKey->id }})" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                                            </button>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-page-shell>
