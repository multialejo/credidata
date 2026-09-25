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
  <span style="color: #f0abfc">-d '{"identificador":"1713175071","tipo_dato":"telefono","valor":"+593 99 123 4567"}'</span></code></pre>
        </div>
        <h3 class="mt-6 text-sm font-bold text-[#14213d]">Requisitos</h3>
        <ul class="mt-2 space-y-1.5 text-sm leading-6 text-slate-600">
            <li>API Key activa con el permiso <code class="font-mono text-xs">colaboradores:aportes</code>, o el comodín <code class="font-mono text-xs">colaboradores:*</code>.</li>
            <li>Colaborador activo. Si aún no lo eres, actívalo con <code class="font-mono text-xs">POST /api/v1/colaboradores/registro</code> y el permiso <code class="font-mono text-xs">colaboradores:registro</code>.</li>
            <li>Si tu API Key restringe IPs, la de la petición debe estar en la lista permitida.</li>
        </ul>
        <h3 class="mt-6 text-sm font-bold text-[#14213d]">Parámetros</h3>
        <div class="mt-2 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead><tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500"><th class="py-2 pr-4 font-semibold">Campo</th><th class="py-2 pr-4 font-semibold">Valores</th><th class="py-2 font-semibold">Regla</th></tr></thead>
                <tbody class="divide-y divide-slate-100 text-slate-600">
                    <tr><td class="py-2 pr-4 font-mono text-xs text-[#14213d]">identificador</td><td class="py-2 pr-4">Cédula o RUC</td><td class="py-2">Obligatorio. Debe superar la validación de identificador ecuatoriano.</td></tr>
                    <tr><td class="py-2 pr-4 font-mono text-xs text-[#14213d]">tipo_dato</td><td class="py-2 pr-4"><code class="font-mono text-xs">telefono</code>, <code class="font-mono text-xs">email</code>, <code class="font-mono text-xs">direccion</code></td><td class="py-2">Obligatorio.</td></tr>
                    <tr><td class="py-2 pr-4 font-mono text-xs text-[#14213d]">valor</td><td class="py-2 pr-4">Texto</td><td class="py-2">Obligatorio, máximo 500 caracteres. El formato se valida según <code class="font-mono text-xs">tipo_dato</code>: email válido, teléfono de 7 a 30 caracteres, dirección de al menos 5.</td></tr>
                </tbody>
            </table>
        </div>
        <h3 class="mt-6 text-sm font-bold text-[#14213d]">Respuesta · HTTP 201</h3>
        <p class="mt-1 text-sm leading-6 text-slate-600">El ejemplo siguiente corresponde a un campo que <strong class="font-semibold text-[#14213d]">estaba vacío</strong>. Si el campo ya tenía un valor, el mismo cuerpo devuelve <code class="font-mono text-xs">"estado": "pendiente"</code> en lugar de <code class="font-mono text-xs">"aprobado"</code> y <code class="font-mono text-xs">recompensa_creditos</code> llega en <code class="font-mono text-xs">null</code>.</p>
        <pre class="mt-2 overflow-x-auto rounded-xl bg-slate-100 p-4 font-mono text-xs leading-5 text-slate-800"><code>{
  "codigo": 201,
  "exito": true,
  "mensaje": "Aporte registrado",
  "datos": {
    "aporte": {
      "id": 1,
      "identificador": "1713175071",
      "tipo_dato": "telefono",
      "valor": "+593 99 123 4567",
      "estado": "aprobado",
      "comentario": null,
      "recompensa_creditos": 1,
      "fecha": "2026-09-25T16:41:37+00:00",
      "revisado_en": null
    }
  }
}</code></pre>
        <div class="mt-4 rounded-xl border-l-4 border-[#2647c2] bg-[#eef1fb] p-4">
            <p class="text-sm font-semibold text-[#14213d]">Lee siempre <code class="font-mono text-xs">datos.aporte.estado</code></p>
            <p class="mt-1 text-sm leading-6 text-slate-600">Ambas respuestas devuelven HTTP 201, pero no significan lo mismo:</p>
            <ul class="mt-2 space-y-1.5 text-sm leading-6 text-slate-600">
                <li><span class="font-mono text-xs text-[#14213d]">aprobado</span> — el campo estaba vacío, así que se aplicó al instante y se acreditaron los créditos de recompensa.</li>
                <li><span class="font-mono text-xs text-[#14213d]">pendiente</span> — el campo ya tenía un valor, así que tu aporte es una reescritura y espera revisión humana. No se acreditan créditos hasta que se apruebe.</li>
            </ul>
            <p class="mt-2 text-sm leading-6 text-slate-600">También existe <span class="font-mono text-xs">rechazado</span>, que indica que la revisión no lo aceptó.</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">El monto de <span class="font-mono text-xs">recompensa_creditos</span> lo fija la configuración de Credidata y puede cambiar.</p>
        </div>
        <h3 class="mt-6 text-sm font-bold text-[#14213d]">Errores frecuentes</h3>
        <div class="mt-2 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead><tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500"><th class="py-2 pr-4 font-semibold">HTTP</th><th class="py-2 pr-4 font-semibold">error.tipo</th><th class="py-2 font-semibold">Causa</th></tr></thead>
                <tbody class="divide-y divide-slate-100 text-slate-600">
                    <tr><td class="py-2 pr-4 font-mono text-xs text-[#14213d]">401</td><td class="py-2 pr-4 font-mono text-xs">API_KEY_REQUERIDA</td><td class="py-2">Falta la cabecera <code class="font-mono text-xs">Authorization</code>.</td></tr>
                    <tr><td class="py-2 pr-4 font-mono text-xs text-[#14213d]">401</td><td class="py-2 pr-4 font-mono text-xs">API_KEY_REVOCADA</td><td class="py-2">La key fue rotada o revocada. Usa la más reciente.</td></tr>
                    <tr><td class="py-2 pr-4 font-mono text-xs text-[#14213d]">403</td><td class="py-2 pr-4 font-mono text-xs">PERMISO_INSUFICIENTE</td><td class="py-2">La key no tiene <code class="font-mono text-xs">colaboradores:aportes</code>. Amplíalo desde el panel; la propia API no puede hacerlo.</td></tr>
                    <tr><td class="py-2 pr-4 font-mono text-xs text-[#14213d]">403</td><td class="py-2 pr-4 font-mono text-xs">IP_NO_PERMITIDA</td><td class="py-2">Tu key restringe IPs y la de esta petición no está en la lista.</td></tr>
                    <tr><td class="py-2 pr-4 font-mono text-xs text-[#14213d]">403</td><td class="py-2 pr-4 font-mono text-xs">COLABORADOR_NO_ACTIVO</td><td class="py-2">Te falta activar el colaborador.</td></tr>
                </tbody>
            </table>
        </div>
    </section>
    <details class="ui-card group p-5 sm:p-7">
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
