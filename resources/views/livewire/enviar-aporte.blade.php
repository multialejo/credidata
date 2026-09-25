<x-page-shell max-width="3xl">
    <x-page-header eyebrow="Colaboradores" title="Enviar aporte" description="Aporta un dato a tu ficha para mejorar las consultas de Credidata. Puedes hacerlo por API o con el formulario de abajo." />
    <section class="ui-card p-5 sm:p-7" aria-labelledby="api-title">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="api-title" class="ui-section-title text-xl">Enviar por API</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">Reemplaza la variable por tu API Key activa.</p>
            </div>
            <span class="font-mono text-xs font-semibold uppercase tracking-wide text-slate-500">POST · JSON</span>
        </div>
        <div class="mt-4 overflow-hidden rounded-xl bg-[#0d1630] ring-1 ring-[#14213d]/10">
            <div class="flex items-center justify-between border-b border-white/10 px-4 py-2.5">
                <span class="text-xs font-semibold text-slate-300">Terminal</span>
                <span class="font-mono text-xs text-slate-400">colaboradores-datos</span>
            </div>
            <pre class="overflow-x-auto p-4 font-mono text-sm leading-6" style="color: #f8fafc !important"><code style="color: #f8fafc !important"><span style="color: #93c5fd">curl</span> -X POST <span style="color: #fcd34d">"{{ url('/api/v1/colaboradores/datos') }}"</span> \
  <span style="color: #86efac">-H "Authorization: Bearer $CREDIDATA_API_KEY"</span> \
  <span style="color: #86efac">-H "Content-Type: application/json"</span> \
  <span style="color: #86efac">-H "Accept: application/json"</span> \
  <span style="color: #f0abfc">-d '{"identificador":"1713175071","tipo_dato":"telefono","valor":"0991234567"}'</span></code></pre>
        </div>
        <p class="mt-5 text-sm leading-6 text-slate-600">Consulta los requisitos, parámetros, respuestas y errores del endpoint en <a class="font-semibold text-[#3155d9] underline underline-offset-2 hover:text-[#2647c2]" href="{{ route('dashboard.documentacion') }}#contribution-api">la documentación de la API</a>.</p>
    </section>
    <details id="web-form" class="ui-card group p-5 sm:p-7">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
            <span>
                <span class="ui-section-title text-xl">Prefiero el formulario</span>
                <span class="mt-1 block text-sm leading-6 text-slate-600">Envía el mismo dato desde el navegador, sin escribir una petición.</span>
            </span>
            <span class="shrink-0 text-slate-400 transition group-open:rotate-180" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
            </span>
        </summary>
        @if($resultado) <x-alert variant="{{ $resultado === 'aprobado' ? 'success' : 'warning' }}" class="mt-5 mb-6">{{ $resultado === 'aprobado' ? "El dato fue aplicado y se acreditaron {$recompensaAcreditada} créditos a tu cuenta." : 'Tu aporte quedó pendiente de revisión.' }}</x-alert> @endif
        <form wire:submit="enviar" class="mt-5 space-y-5">
            <div><x-input-label for="identificador" value="Cédula o RUC"/><x-text-input id="identificador" wire:model="identificador" class="mt-1"/>@error('identificador')<p class="ui-error" role="alert">{{ $message }}</p>@enderror</div>
            <div><x-input-label for="tipoDato" value="Tipo de dato"/><select id="tipoDato" wire:model="tipoDato" class="ui-input mt-1 w-full"><option value="telefono">Teléfono</option><option value="email">Correo electrónico</option><option value="direccion">Dirección</option></select></div>
            <div><x-input-label for="valor" value="Valor"/><x-text-input id="valor" wire:model="valor" class="mt-1"/>@error('valor')<p class="ui-error" role="alert">{{ $message }}</p>@enderror</div>
            <x-primary-button wire:loading.attr="disabled" wire:target="enviar">
                <span wire:loading.remove wire:target="enviar">Enviar aporte</span>
                <span wire:loading wire:target="enviar">Enviando...</span>
            </x-primary-button>
        </form>
    </details>
</x-page-shell>
