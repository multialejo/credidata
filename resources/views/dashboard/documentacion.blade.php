<x-app-layout>
<x-page-shell max-width="6xl">
    <x-page-header
        title="Documentación de la API"
        description="Conecta tu aplicación con las consultas de cédula y RUC de CrediData. Sigue estos pasos para realizar tu primera petición."
    />

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_14rem] xl:items-start">
        <div class="min-w-0 space-y-6">

<section id="quickstart" class="ui-card scroll-mt-8 p-5 sm:p-7" aria-labelledby="quickstart-title">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-5">
        <div>
            <h2 id="quickstart-title" class="ui-section-title text-xl font-bold text-[#14213d]">Quickstart</h2>
            <p class="mt-1 text-sm text-slate-600">Tres pasos simples para enviar tu primera consulta.</p>
        </div>
        <a href="{{ url('/api/documentation') }}" class="ui-secondary-button inline-flex items-center justify-center gap-2 text-xs sm:text-sm">
            <span>Abrir referencia OpenAPI</span>
            <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
            </svg>
        </a>
    </div>

    <!-- Steps Grid -->
    <ol class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <!-- Paso 1 -->
        <li class="relative flex flex-col justify-between rounded-xl border border-slate-200/80 bg-slate-50/50 p-5 transition-all hover:border-slate-300 hover:bg-white hover:shadow-sm">
            <div>
                <div class="flex items-center justify-between gap-3">
                    <span aria-hidden="true" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#e8edf9] text-sm font-bold text-[#2647c2]">
                        1
                    </span>
                </div>
                <h3 class="mt-4 font-semibold text-[#14213d]">Genera tu API Key</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                    Crea y administra tus credenciales desde la sección de
                    <a class="inline-flex items-center gap-1 font-semibold text-[#3155d9] hover:underline underline-offset-2" href="{{ route('dashboard.api-key') }}">
                        API Key
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </p>
            </div>
        </li>

        <!-- Paso 2 -->
        <li class="relative flex flex-col justify-between rounded-xl border border-slate-200/80 bg-slate-50/50 p-5 transition-all hover:border-slate-300 hover:bg-white hover:shadow-sm">
            <div>
                <div class="flex items-center justify-between gap-3">
                    <span aria-hidden="true" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#e8edf9] text-sm font-bold text-[#2647c2]">
                        2
                    </span>
                </div>
                <h3 class="mt-4 font-semibold text-[#14213d]">Habilita el permiso</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                    Activa los scopes necesarios:
                    <code class="inline-block rounded-md border border-slate-200 bg-white px-1.5 py-0.5 font-mono text-xs text-slate-800 shadow-2xs">consulta:cedula</code>,
                    <code class="inline-block rounded-md border border-slate-200 bg-white px-1.5 py-0.5 font-mono text-xs text-slate-800 shadow-2xs">consulta:ruc</code> o ambos.
                </p>
            </div>
        </li>

        <!-- Paso 3 -->
        <li class="relative flex flex-col justify-between rounded-xl border border-slate-200/80 bg-slate-50/50 p-5 transition-all hover:border-slate-300 hover:bg-white hover:shadow-sm">
            <div>
                <div class="flex items-center justify-between gap-3">
                    <span aria-hidden="true" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#e8edf9] text-sm font-bold text-[#2647c2]">
                        3
                    </span>
                </div>
                <h3 class="mt-4 font-semibold text-[#14213d]">Envía la consulta</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                    Realiza una petición <code class="rounded bg-emerald-50 px-1.5 py-0.5 font-mono text-xs font-semibold text-emerald-700">POST</code> adjuntando tu token como <code class="rounded bg-slate-100 px-1 font-mono text-xs">Bearer</code> en los headers.
                </p>
            </div>
        </li>
    </ol>
</section>

<section id="curl-example" class="ui-card scroll-mt-8 p-5 sm:p-7" aria-labelledby="curl-title">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="curl-title" class="ui-section-title text-xl">Prueba rápida con curl</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">Reemplaza la variable por tu API Key activa.</p>
            </div>
            <span class="font-mono text-xs font-semibold uppercase tracking-wide text-slate-500">POST · JSON</span>
        </div>
        <div class="mt-4 overflow-hidden rounded-xl bg-[#0d1630] ring-1 ring-[#14213d]/10">
            <div class="flex items-center justify-between border-b border-white/10 px-4 py-2.5">
                <span class="text-xs font-semibold text-slate-300">Terminal</span>
                <span class="font-mono text-xs text-slate-400">consulta-cedula</span>
            </div>
            <pre class="overflow-x-auto p-4 font-mono text-sm leading-6" style="color: #f8fafc !important"><code style="color: #f8fafc !important"><span style="color: #93c5fd">curl</span> -X POST <span style="color: #fcd34d">"{{ url('/api/v1/consulta/cedula') }}"</span> \
  <span style="color: #86efac">-H "Authorization: Bearer $CREDIDATA_API_KEY"</span> \
  <span style="color: #86efac">-H "Content-Type: application/json"</span> \
  <span style="color: #86efac">-H "Accept: application/json"</span> \
  <span style="color: #f0abfc">-d '{"cedula":"1713175071"}'</span></code></pre>
        </div>
        <br>
        <div class="mt-5 grid gap-10 lg:grid-cols-2">
            <div>
                <h3 class="text-sm font-bold text-[#14213d]">Respuesta exitosa · HTTP 200</h3>
                <p class="mt-1 text-sm leading-6 text-slate-600">Extracto: <code class="font-mono text-xs">datos</code> contiene el resultado y <code class="font-mono text-xs">metadatos</code> informa el consumo.</p>
                <pre class="mt-3 overflow-x-auto rounded-xl bg-slate-100 p-4 font-mono text-xs leading-5 text-slate-800"><code>{
  "codigo": 200,
  "exito": true,
  "mensaje": "Consulta exitosa",
  "datos": {
    "cedula": "1713175071",
    "nombres": "Juan Carlos Pérez García"
  },
  "metadatos": {
    "creditos_gastados": 1,
    "creditos_restantes": 99,
    "fuente": "dinardap"
  }
}</code></pre>
            </div>
            <div>
                <h3 class="text-sm font-bold text-[#14213d]">Si la consulta falla</h3>
                <ul class="mt-2 space-y-3 text-sm leading-6 text-slate-600">
                    <li><span class="font-semibold text-[#14213d]">401 · API Key:</span> revisa que el encabezado Bearer contenga una key activa y no revocada. No se consulta la fuente.</li>
                    <li><span class="font-semibold text-[#14213d]">402 · Saldo insuficiente:</span> recarga créditos y vuelve a intentar. La respuesta incluye <code class="font-mono text-xs">metadatos.creditos_restantes</code>.</li>
                    <li><span class="font-semibold text-[#14213d]">403 · Permiso insuficiente:</span> habilita <code class="font-mono text-xs">consulta:cedula</code> en tu API Key y vuelve a enviar la petición.</li>
                    <li><span class="font-semibold text-[#14213d]">422 · Cédula inválida:</span> corrige el valor indicado en <code class="font-mono text-xs">errors.cedula</code>. La validación no consume créditos.</li>
                </ul>
            </div>
        </div>
        <br>
        <x-alert variant="info" class="mt-4">Una consulta sin resultados puede consumir créditos. Los errores de validación, autorización o saldo insuficiente no realizan la consulta.</x-alert>
    </section>

    <section class="space-y-5" aria-labelledby="endpoints-title">
        <div>
            <h2 id="endpoints-title" class="ui-section-title text-xl">Consultas y campos</h2>
            <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">Ambas consultas usan una respuesta común con <code class="rounded bg-slate-100 px-1 font-mono text-xs">codigo</code>, <code class="rounded bg-slate-100 px-1 font-mono text-xs">exito</code>, <code class="rounded bg-slate-100 px-1 font-mono text-xs">mensaje</code>, <code class="rounded bg-slate-100 px-1 font-mono text-xs">datos</code> y <code class="rounded bg-slate-100 px-1 font-mono text-xs">metadatos</code>.</p>
        </div>

        <section id="cedula-fields" class="ui-card scroll-mt-8 p-5 sm:p-7" aria-labelledby="cedula-fields-title">
            <div class="flex flex-col gap-2 border-b border-slate-200 pb-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 id="cedula-fields-title" class="text-lg font-bold text-[#14213d]">Consulta por cédula</h3>
                    <p class="mt-1 text-sm text-slate-600">Campos normalizados dentro de <code class="font-mono text-xs">datos</code>.</p>
                </div>
                <code class="w-fit break-all rounded-lg bg-[#e8edf9] px-3 py-2 font-mono text-xs font-semibold text-[#2647c2]">POST /api/v1/consulta/cedula</code>
            </div>
            <p class="mt-4 text-sm leading-6 text-slate-600"><span class="font-semibold text-[#14213d]">Cuerpo requerido:</span> <code class="rounded bg-slate-100 px-1 font-mono text-xs">cedula</code>, string con una cédula ecuatoriana válida.</p>

            <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full min-w-[42rem] divide-y divide-slate-200 text-left text-sm">
                    <caption class="sr-only">Detalle de campos devueltos en una consulta por cédula</caption>
                    <thead class="bg-[#14213d] text-xs uppercase tracking-wide text-white">
                        <tr>
                            <th scope="col" class="w-56 px-4 py-3 font-semibold">Campo</th>
                            <th scope="col" class="w-36 px-4 py-3 font-semibold">Tipo</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Descripción y ejemplo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        <tr><th scope="row" class="px-4 py-3 font-mono text-xs font-semibold text-[#14213d]">cedula</th><td class="px-4 py-3">string</td><td class="px-4 py-3">Cédula consultada. Ej.: <code class="font-mono text-xs">"1713175071"</code>.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 font-mono text-xs font-semibold text-[#14213d]">nombres</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Nombres completos; puede venir vacío o nulo si la fuente no lo informa.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 font-mono text-xs font-semibold text-[#14213d]">profesion</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Profesión registrada, si está disponible.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 font-mono text-xs font-semibold text-[#14213d]">fechaNacimiento</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Fecha de nacimiento; normalmente formato <code class="font-mono text-xs">YYYY-MM-DD</code>. Ej.: <code class="font-mono text-xs">"1985-06-15"</code>.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 font-mono text-xs font-semibold text-[#14213d]">lugarNacimiento</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Lugar de nacimiento. Ej.: <code class="font-mono text-xs">"Quito"</code>.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 font-mono text-xs font-semibold text-[#14213d]">estadoCivilCodigo</th><td class="px-4 py-3">number | string | null</td><td class="px-4 py-3">Código de estado civil según el dato recibido de la fuente.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 font-mono text-xs font-semibold text-[#14213d]">conyuge</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Nombre del cónyuge, si consta en el registro.</td></tr>
                        <tr class="bg-slate-50"><th scope="rowgroup" colspan="3" class="px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-600">Objeto ubicacion</th></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">ubicacion.provincia</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Provincia asociada a la ubicación. Ej.: <code class="font-mono text-xs">"Pichincha"</code>.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">ubicacion.canton</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Cantón asociado a la ubicación. Ej.: <code class="font-mono text-xs">"Quito"</code>.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">ubicacion.parroquia</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Parroquia asociada a la ubicación.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 font-mono text-xs font-semibold text-[#14213d]">ruc</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">RUC relacionado con la persona, cuando la fuente lo proporciona.</td></tr>
                        <tr class="bg-slate-50"><th scope="rowgroup" colspan="3" class="px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-600">Objeto contacto</th></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">contacto.telefonos</th><td class="px-4 py-3">array&lt;string&gt;</td><td class="px-4 py-3">Lista de teléfonos. Si no hay datos, devuelve <code class="font-mono text-xs">[]</code>.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">contacto.emails</th><td class="px-4 py-3">array&lt;string&gt;</td><td class="px-4 py-3">Lista de correos electrónicos. Si no hay datos, devuelve <code class="font-mono text-xs">[]</code>.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">contacto.direcciones</th><td class="px-4 py-3">array&lt;string&gt;</td><td class="px-4 py-3">Lista de direcciones. Si no hay datos, devuelve <code class="font-mono text-xs">[]</code>.</td></tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs leading-5 text-slate-500">Los campos opcionales sin información se representan como <code class="font-mono">null</code>; <code class="font-mono">ubicacion</code> y <code class="font-mono">contacto</code> conservan su estructura.</p>
        </section>

        <section id="ruc-fields" class="ui-card scroll-mt-8 p-5 sm:p-7" aria-labelledby="ruc-fields-title">
            <div class="flex flex-col gap-2 border-b border-slate-200 pb-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 id="ruc-fields-title" class="text-lg font-bold text-[#14213d]">Consulta por RUC</h3>
                    <p class="mt-1 text-sm text-slate-600">Devuelve el RUC y los establecimientos registrados.</p>
                </div>
                <code class="w-fit break-all rounded-lg bg-[#e8edf9] px-3 py-2 font-mono text-xs font-semibold text-[#2647c2]">POST /api/v1/consulta/ruc</code>
            </div>
            <p class="mt-4 text-sm leading-6 text-slate-600"><span class="font-semibold text-[#14213d]">Cuerpo requerido:</span> <code class="rounded bg-slate-100 px-1 font-mono text-xs">ruc</code>, string de 13 dígitos correspondiente a un RUC ecuatoriano válido.</p>

            <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full min-w-[42rem] divide-y divide-slate-200 text-left text-sm">
                    <caption class="sr-only">Detalle de campos devueltos en una consulta por RUC</caption>
                    <thead class="bg-[#14213d] text-xs uppercase tracking-wide text-white">
                        <tr>
                            <th scope="col" class="w-64 px-4 py-3 font-semibold">Campo</th>
                            <th scope="col" class="w-36 px-4 py-3 font-semibold">Tipo</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Descripción y ejemplo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        <tr><th scope="row" class="px-4 py-3 font-mono text-xs font-semibold text-[#14213d]">ruc</th><td class="px-4 py-3">string</td><td class="px-4 py-3">RUC consultado. Ej.: <code class="font-mono text-xs">"0991234567001"</code>.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 font-mono text-xs font-semibold text-[#14213d]">establecimientos</th><td class="px-4 py-3">array&lt;object&gt;</td><td class="px-4 py-3">Lista de establecimientos; puede estar vacía cuando no hay registros.</td></tr>
                        <tr class="bg-slate-50"><th scope="rowgroup" colspan="3" class="px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-600">Campos por establecimiento</th></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">numero</th><td class="px-4 py-3">string</td><td class="px-4 py-3">Número identificador del establecimiento.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">razonSocial</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Razón social registrada.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">nombreComercial</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Nombre de fantasía o nombre comercial.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">estadoContribuyente</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Estado del contribuyente informado por el SRI.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">estadoEstablecimiento</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Estado particular del establecimiento.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">tipoContribuyente</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Tipo de contribuyente registrado.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">claseContribuyente</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Clase de contribuyente registrada.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">obligadoContabilidad</th><td class="px-4 py-3">boolean</td><td class="px-4 py-3">Indica si está obligado a llevar contabilidad.</td></tr>
                        <tr class="bg-slate-50"><th scope="rowgroup" colspan="3" class="px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-600">Objeto actividadEconomica</th></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">actividadEconomica.codigo</th><td class="px-4 py-3">string | number | null</td><td class="px-4 py-3">Código CIIU informado por la fuente.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">actividadEconomica.descripcion</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Descripción de la actividad económica principal.</td></tr>
                        <tr class="bg-slate-50"><th scope="rowgroup" colspan="3" class="px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-600">Objeto ubicacion</th></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">ubicacion.jurisdiccion</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Jurisdicción del establecimiento.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">ubicacion.provincia</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Provincia donde se ubica.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">ubicacion.canton</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Cantón donde se ubica.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">ubicacion.parroquia</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Parroquia donde se ubica.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">ubicacion.direccion</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Dirección completa del establecimiento.</td></tr>
                        <tr class="bg-slate-50"><th scope="rowgroup" colspan="3" class="px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-600">Objeto fechas</th></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">fechas.inicioActividades</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Fecha de inicio de actividades, en formato <code class="font-mono text-xs">YYYY-MM-DD</code>.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">fechas.reinicioActividades</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Fecha de reinicio, si aplica.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">fechas.suspensionDefinitiva</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Fecha de suspensión definitiva, si aplica.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">fechas.actualizacion</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Fecha de actualización del registro.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">agenteRetencion</th><td class="px-4 py-3">boolean</td><td class="px-4 py-3">Indica si figura como agente de retención.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">contribuyenteEspecial</th><td class="px-4 py-3">boolean</td><td class="px-4 py-3">Indica si figura como contribuyente especial.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">artesanoCalificado</th><td class="px-4 py-3">boolean</td><td class="px-4 py-3">Indica si consta como artesano calificado.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">regimenRimpe</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Régimen RIMPE registrado, si está disponible.</td></tr>
                        <tr class="bg-slate-50"><th scope="rowgroup" colspan="3" class="px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-600">Objeto contacto</th></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">contacto.email</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Correo de contacto registrado.</td></tr>
                        <tr><th scope="row" class="px-4 py-3 pl-7 font-mono text-xs font-semibold text-[#14213d]">contacto.telefono</th><td class="px-4 py-3">string | null</td><td class="px-4 py-3">Teléfono de contacto registrado.</td></tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs leading-5 text-slate-500">Los nombres y tipos de las propiedades de establecimiento corresponden al normalizador de Catastro SRI. Los campos que la fuente no informa se devuelven como <code class="font-mono">null</code>; fechas, ubicaciones y contacto siempre mantienen su objeto.</p>
        </section>
    </section>

    </div>

    <aside class="hidden xl:block xl:self-stretch" aria-label="Índice de documentación">
        <nav class="ui-card sticky top-6 p-4" aria-labelledby="documentation-index-title">
            <h2 id="documentation-index-title" class="text-sm font-bold text-[#14213d]">En esta página</h2>
            <ul class="mt-3 space-y-1 border-l border-slate-200 pl-3 text-sm">
                <li><a href="#quickstart" class="block rounded-r-lg py-2 text-slate-600 underline-offset-4 transition hover:bg-[#e8edf9] hover:text-[#2647c2] hover:underline focus-visible:text-[#2647c2]">Quickstart</a></li>
                <li><a href="#cedula-fields" class="block rounded-r-lg py-2 text-slate-600 underline-offset-4 transition hover:bg-[#e8edf9] hover:text-[#2647c2] hover:underline focus-visible:text-[#2647c2]">Consulta por cédula</a></li>
                <li><a href="#ruc-fields" class="block rounded-r-lg py-2 text-slate-600 underline-offset-4 transition hover:bg-[#e8edf9] hover:text-[#2647c2] hover:underline focus-visible:text-[#2647c2]">Consulta por RUC</a></li>
                <li><a href="#curl-example" class="block rounded-r-lg py-2 text-slate-600 underline-offset-4 transition hover:bg-[#e8edf9] hover:text-[#2647c2] hover:underline focus-visible:text-[#2647c2]">Prueba con curl</a></li>
            </ul>
        </nav>
    </aside>
    </div>
</x-page-shell>
</x-app-layout>
