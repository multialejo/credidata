<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'CrediData') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="font-sans antialiased bg-[#f7f5ef] text-[#14213d]">

        {{-- NAVBAR --}}
        <header class="fixed inset-x-0 top-0 z-40 border-b border-slate-200/70 bg-[#f7f5ef]/85 backdrop-blur-md" x-data="{ open: false }">
            <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="/" class="flex items-center gap-2.5" aria-label="CrediData inicio">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#3155d9] text-base font-extrabold tracking-tight text-white shadow-sm">C</span>
                    <span class="text-lg font-extrabold tracking-tight text-[#14213d]">CrediData</span>
                </a>

                <div class="hidden items-center gap-7 text-sm font-medium text-slate-600 md:flex">
                    <a href="#consultas" class="transition hover:text-[#3155d9]">Consultas</a>
                    <a href="#desarrolladores" class="transition hover:text-[#3155d9]">Desarrolladores</a>
                    <a href="#precios" class="transition hover:text-[#3155d9]">Precios</a>
                    <a href="#faq" class="transition hover:text-[#3155d9]">Preguntas</a>
                </div>

                <div class="hidden items-center gap-3 md:flex">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="ui-primary-button">Ir al panel</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-[#14213d] transition hover:text-[#3155d9]">Iniciar sesión</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="ui-primary-button">Crear cuenta</a>
                        @endif
                    @endauth
                </div>

                <button type="button" class="rounded-lg p-2 text-[#14213d] hover:bg-slate-200/60 md:hidden" @click="open = !open" aria-label="Abrir menú">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
            </nav>

            <div class="border-t border-slate-200/70 bg-[#f7f5ef] px-4 py-4 md:hidden" x-show="open" x-transition @click.outside="open = false">
                <div class="flex flex-col gap-4 text-sm font-medium text-slate-700">
                    <a href="#consultas" class="transition hover:text-[#3155d9]">Consultas</a>
                    <a href="#desarrolladores" class="transition hover:text-[#3155d9]">Desarrolladores</a>
                    <a href="#precios" class="transition hover:text-[#3155d9]">Precios</a>
                    <a href="#faq" class="transition hover:text-[#3155d9]">Preguntas</a>
                    <div class="mt-2 flex flex-col gap-3 border-t border-slate-200 pt-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="ui-primary-button w-full">Ir al panel</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-semibold text-[#14213d]">Iniciar sesión →</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="ui-primary-button w-full">Crear cuenta</a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        {{-- HERO --}}
        <section class="relative overflow-hidden pt-16">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="absolute -top-24 right-[-10%] h-96 w-96 rounded-full bg-[#3155d9]/15 blur-3xl"></div>
                <div class="absolute top-40 left-[-8%] h-80 w-80 rounded-full bg-[#3155d9]/10 blur-3xl"></div>
                <div class="absolute inset-0 opacity-[0.5] [background-image:radial-gradient(#14213d_1px,transparent_1px)] [background-size:28px_28px]"></div>
            </div>

            <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 pb-20 pt-14 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8 lg:pt-20">
                <div>
                    <h1 class="mt-6 text-4xl font-extrabold leading-[1.08] tracking-tight text-[#14213d] sm:text-5xl lg:text-6xl">
                        Consulta datos de Ecuador,<br class="hidden sm:block">
                        <span class="text-[#3155d9]">sin trámites</span>.
                    </h1>

                    <p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-600">
                        Una cédula o un RUC y listo: obtienes la información en segundos y con un formato claro.
                        Tú decides cómo usarla: desde el panel o por una API REST sencilla.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="ui-primary-button px-6 py-3 text-base">Ir al panel</a>
                        @else
                            <a href="{{ route('register') }}" class="ui-primary-button px-6 py-3 text-base">Crear cuenta gratis</a>
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="ui-secondary-button px-6 py-3 text-base">Iniciar sesión</a>
                            @endif
                        @endauth
                    </div>
                </div>

                <div class="relative">
                    <div class="ui-card p-6 shadow-[0_24px_60px_-24px_rgba(20,33,61,0.35)]">
                        <div class="flex items-center justify-between">
                            <p class="ui-eyebrow">Prueba de consulta</p>
                        </div>

                        <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between whitespace-nowrap">
                                <div class="min-w-0">
                                    <p class="truncate text-lg font-bold tracking-wide text-[#14213d]">PÉREZ GARCÍA JUAN CARLOS</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-500">CC 1700000000</p>
                                </div>
                            </div>
                            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 border-t border-slate-200 pt-4">
                                <div>
                                    <dt class="text-xs font-medium text-slate-400">Fecha de nacimiento</dt>
                                    <dd class="mt-0.5 text-sm font-semibold text-[#14213d]">12 de marzo de 1990</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-400">Edad</dt>
                                    <dd class="mt-0.5 text-sm font-semibold text-[#14213d]">36 años</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-400">Profesión</dt>
                                    <dd class="mt-0.5 text-sm font-semibold text-[#14213d]">Ingeniero de Sistemas</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-400">Lugar de nacimiento</dt>
                                    <dd class="mt-0.5 text-sm font-semibold text-[#14213d]">Quito, Pichincha</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- PARA PERSONAS --}}
        <section id="consultas" class="scroll-mt-24 bg-white py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl">
                    <p class="ui-eyebrow">Para personas</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-[#14213d] sm:text-4xl">Lo que necesitas, en un solo lugar</h2>
                    <p class="mt-4 text-lg text-slate-600">Consulta rápida, clara y sin complicaciones. Cada consulta queda registrada en tu panel.</p>
                </div>

                <div class="mt-12 grid gap-6 md:grid-cols-3">
                    <div class="ui-card p-6">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#3155d9]/10 text-[#3155d9]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </span>
                        <h3 class="mt-4 text-lg font-bold text-[#14213d]">Consulta por cédula</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">Escribe una cédula y obtén nombres, fecha de nacimiento, profesión y lugar de residencia en segundos.</p>
                    </div>

                    <div class="ui-card p-6">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#3155d9]/10 text-[#3155d9]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </span>
                        <h3 class="mt-4 text-lg font-bold text-[#14213d]">Consulta por RUC</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">Razón social, estado y actividad del contribuyente, directo del SRI, por número de RUC.</p>
                    </div>

                    <div class="ui-card p-6">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#3155d9]/10 text-[#3155d9]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                        </span>
                        <h3 class="mt-4 text-lg font-bold text-[#14213d]">Historial y saldo</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">Tu panel guarda el historial de consultas y tu saldo de créditos. Paga solo por lo que consultas.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- PARA DESARROLLADORES --}}
        <section id="desarrolladores" class="scroll-mt-16 bg-[#0d1630] py-20 text-white">
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
                <div>
                    <p class="ui-eyebrow !text-[#8ea2ff]">Para desarrolladores</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">Una API REST, simple y consistente</h2>
                    <p class="mt-4 text-lg leading-relaxed text-slate-300">
                        Una sola petición HTTP con tu API Key y recibes JSON normalizado. Los mismos campos en todas las consultas,
                        y lo que la fuente no expone llega como <code class="rounded bg-white/10 px-1.5 py-0.5 text-sm text-sky-300">null</code>.
                    </p>

                    <ul class="mt-8 space-y-4 text-sm text-slate-300">
                        <li class="flex gap-3"><span class="mt-0.5 text-[#8ea2ff]">→</span> Integras con tu API Key en minutos: <code class="font-mono text-[#8ea2ff]">Authorization: Bearer cd_sk_...</code></li>
                        <li class="flex gap-3"><span class="mt-0.5 text-[#8ea2ff]">→</span> Códigos de error claros: <code class="font-mono text-[#8ea2ff]">401</code> API Key inválida, <code class="font-mono text-[#8ea2ff]">402</code> saldo insuficiente.</li>
                        <li class="flex gap-3"><span class="mt-0.5 text-[#8ea2ff]">→</span> Solo se cobra si hay respuesta, no por la petición fallida.</li>
                    </ul>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        @auth
                            <a href="{{ url('/dashboard/api-key') }}" class="ui-primary-button px-6 py-3">Crear tu API Key</a>
                        @else
                            <a href="{{ route('register') }}" class="ui-primary-button px-6 py-3">Crear cuenta gratis</a>
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-white/25 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">Iniciar sesión</a>
                            @endif
                        @endauth
                    </div>
                </div>

                <div class="rounded-2xl bg-[#0a1023] p-4 shadow-2xl ring-1 ring-white/10">
                    <div class="flex items-center gap-1.5 border-b border-white/10 px-3 pb-3">
                        <span class="h-3 w-3 rounded-full bg-red-400"></span>
                        <span class="h-3 w-3 rounded-full bg-yellow-400"></span>
                        <span class="h-3 w-3 rounded-full bg-emerald-400"></span>
                        <span class="ml-3 font-mono text-xs text-slate-500">consulta-cedula.sh</span>
                    </div>
                    <pre class="overflow-x-auto p-4 font-mono text-[13px] leading-relaxed">
<code><span class="text-slate-400">$</span> <span class="text-sky-300">curl</span> -X POST <span class="text-sky-300">https://api.credidata.app/api/v1/consulta/cedula</span> \
  <span class="text-sky-300">-H</span> <span class="text-emerald-300">"Authorization: Bearer cd_sk_tu_api_key"</span> \
  <span class="text-sky-300">-H</span> <span class="text-emerald-300">"Content-Type: application/json"</span> \
  <span class="text-sky-300">-d</span> <span class="text-emerald-300">'{"cedula":"1700000000"}'</span>

<span class="text-slate-400"># respuesta</span>
{
  <span class="text-fuchsia-300">"codigo"</span>: <span class="text-amber-300">200</span>,
  <span class="text-fuchsia-300">"exito"</span>: <span class="text-amber-300">true</span>,
  <span class="text-fuchsia-300">"mensaje"</span>: <span class="text-amber-300">null</span>,
  <span class="text-fuchsia-300">"datos"</span>: {
    <span class="text-fuchsia-300">"cedula"</span>: <span class="text-emerald-300">"1700000000"</span>,
    <span class="text-fuchsia-300">"nombres"</span>: <span class="text-emerald-300">"PÉREZ GARCÍA JUAN CARLOS"</span>,
    <span class="text-fuchsia-300">"profesion"</span>: <span class="text-emerald-300">"Ingeniero de Sistemas"</span>,
    <span class="text-fuchsia-300">"fechaNacimiento"</span>: <span class="text-emerald-300">"1990-05-14"</span>,
    <span class="text-fuchsia-300">"lugarNacimiento"</span>: <span class="text-emerald-300">"Quito, Pichincha"</span>,
    <span class="text-fuchsia-300">"ubicacion"</span>: {
      <span class="text-fuchsia-300">"provincia"</span>: <span class="text-emerald-300">"Pichincha"</span>,
      <span class="text-fuchsia-300">"canton"</span>: <span class="text-emerald-300">"Quito"</span>
    },
    <span class="text-fuchsia-300">"contacto"</span>: { <span class="text-fuchsia-300">"telefonos"</span>: [], <span class="text-fuchsia-300">"emails"</span>: [], <span class="text-fuchsia-300">"direcciones"</span>: [] }
  },
  <span class="text-fuchsia-300">"metadatos"</span>: {
    <span class="text-fuchsia-300">"timestamp"</span>: <span class="text-emerald-300">"2026-09-19T12:00:00Z"</span>
  }
}</code>
                    </pre>
                </div>
            </div>
        </section>

        {{-- COLABORADOR --}}
        <section class="bg-white py-20">
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
                <div>
                    <p class="ui-eyebrow">Colaboradores</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-[#14213d] sm:text-4xl">Aporta datos y gana créditos</h2>
                    <p class="mt-4 text-lg leading-relaxed text-slate-600">
                        Encuentras un dato público que todavía no está en CrediData? Aporta la información, nuestro equipo la valida
                        y tú recibes créditos de consulta a cambio. Colaborar te permite consultar sin pagar.
                    </p>
                    <a href="{{ url('/dashboard/colaborador') }}" class="mt-6 inline-flex items-center gap-1 font-semibold text-[#3155d9] transition hover:gap-2 hover:text-[#2647c2]">
                        Conoce cómo colaborar <span aria-hidden="true">→</span>
                    </a>
                </div>

                <ol class="space-y-5">
                    @php
                        $pasos = [
                            'Envía el dato' => 'Compartes la información o el documento que falta, desde tu panel.',
                            'Validamos' => 'Nuestro equipo verifica que el dato sea correcto y de fuente pública.',
                            'Ganas créditos' => 'Acreditamos créditos a tu saldo por cada aporte aprobado.',
                        ];
                    @endphp
                    @foreach ($pasos as $titulo => $descripcion)
                        <li class="ui-card flex gap-5 p-6">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#3155d9] text-sm font-bold text-white">{{ $loop->index + 1 }}</span>
                            <div>
                                <h3 class="font-bold text-[#14213d]">{{ $titulo }}</h3>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $descripcion }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- PRECIOS --}}
        <section id="precios" class="scroll-mt-24 py-20">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="ui-eyebrow">Precios</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-[#14213d] sm:text-4xl">Paga por consulta. Nada más</h2>
                    <p class="mt-4 text-lg text-slate-600">Sin planes, sin mensualidades y sin permanencia. Recargan cuando lo necesites y usas tus créditos a tu ritmo.</p>
                </div>

                <div class="mt-12 ui-card p-8 sm:p-10">
                    <ul class="grid gap-5 sm:grid-cols-2">
                        @foreach ([
                            'Recargas desde $0.50 con PayPal, PayPhone o transferencia.',
                            'Solo se cobra si hay respuesta, no por la petición fallida.',
                            'Tu historial y saldo quedan registrados en tu panel.',
                            'Colaborando con datos puedes obtener créditos sin pagar.',
                        ] as $beneficio)
                            <li class="flex gap-3 text-sm leading-relaxed text-slate-700">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                </span>
                                {{ $beneficio }}
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-8 flex flex-col gap-3 border-t border-slate-200 pt-8 sm:flex-row sm:justify-center">
                        @auth
                            <a href="{{ url('/dashboard/recargas') }}" class="ui-primary-button px-6 py-3">Recargar saldo</a>
                        @else
                            <a href="{{ route('register') }}" class="ui-primary-button px-6 py-3">Crear cuenta gratis</a>
                            @if (Route::has('login'))
                                <a href="{{ route('login') }}" class="ui-secondary-button px-6 py-3">Iniciar sesión</a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="scroll-mt-24 bg-white py-20">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="ui-eyebrow">Preguntas frecuentes</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-[#14213d] sm:text-4xl">Resolvemos tus dudas</h2>
                </div>

                <div class="mt-10 space-y-3">
                    @foreach ([
                        '¿Qué es CrediData?' => 'Una plataforma para consultar datos de identidad (cédula) y empresas (RUC) de Ecuador, ya sea desde el panel o mediante una API REST para desarrolladores.',
                        '¿Qué consultas puedo hacer?' => 'Hoy puedes consultar por cédula (Registro Civil) y por RUC (SRI). Se irán sumando más fuentes de datos públicos con el tiempo.',
                        '¿Cómo pago por las consultas?' => 'Recargas saldo en créditos desde $0.50 con PayPal, PayPhone o transferencia. Cada consulta consume un crédito y solo se cobra si hay respuesta.',
                        '¿Qué es una API Key?' => 'Es la credencial que usas para consumir la API. La generas desde tu panel y la envías en el encabezado Authorization de cada petición.',
                        '¿Puedo obtener créditos sin pagar?' => 'Sí. Como colaborador puedes aportar datos públicos que falten y, una vez validados, recibes créditos de consulta a cambio.',
                        '¿Es legal y seguro?' => 'CrediData es una plataforma privada e independiente, no afiliada al gobierno. Consultamos información pública y tratamos tus datos conforme a la legislación ecuatoriana.',
                    ] as $pregunta => $respuesta)
                        <details class="ui-card group px-6 py-5">
                            <summary class="flex list-none cursor-pointer items-center justify-between gap-4 font-semibold text-[#14213d]">
                                <span>{{ $pregunta }}</span>
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition group-open:rotate-45 group-open:bg-[#3155d9] group-open:text-white" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                </span>
                            </summary>
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $respuesta }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- CTA FINAL --}}
        <section class="bg-[#0d1630] py-20">
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Empieza a consultar hoy</h2>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-300">Crea tu cuenta gratis y haz tu primera consulta en menos de un minuto.</p>
                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="ui-primary-button px-8 py-3 text-base">Ir al panel</a>
                    @else
                        <a href="{{ route('register') }}" class="ui-primary-button px-8 py-3 text-base">Crear cuenta gratis</a>
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl border border-white/25 px-8 py-3 text-base font-semibold text-white transition hover:bg-white/10">Iniciar sesión</a>
                        @endif
                    @endauth
                </div>
            </div>
        </section>

        {{-- FOOTER --}}
        <footer class="bg-[#0a1023] py-14 text-slate-400">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-10 md:grid-cols-[1.4fr_1fr_1fr_1fr]">
                    <div>
                        <a href="/" class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#3155d9] text-base font-extrabold tracking-tight text-white">C</span>
                            <span class="text-lg font-extrabold tracking-tight text-white">CrediData</span>
                        </a>
                        <p class="mt-4 max-w-xs text-sm leading-relaxed">Acceso simple y confiable a datos públicos del Ecuador, para personas y desarrolladores.</p>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-white">Consultas</h3>
                        <ul class="mt-4 space-y-2.5 text-sm">
                            <li><a href="#consultas" class="transition hover:text-white">Cédula</a></li>
                            <li><a href="#consultas" class="transition hover:text-white">RUC</a></li>
                            <li><a href="{{ url('/dashboard/consultas') }}" class="transition hover:text-white">Historial</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-white">Desarrolladores</h3>
                        <ul class="mt-4 space-y-2.5 text-sm">
                            <li><a href="#desarrolladores" class="transition hover:text-white">API</a></li>
                            <li><a href="#precios" class="transition hover:text-white">Precios</a></li>
                            <li><a href="{{ url('/dashboard/api-key') }}" class="transition hover:text-white">API Key</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-white">Ayuda</h3>
                        <ul class="mt-4 space-y-2.5 text-sm">
                            <li><a href="#faq" class="transition hover:text-white">Preguntas frecuentes</a></li>
                            <li><a href="#faq" class="transition hover:text-white">Privacidad</a></li>
                            <li><a href="#faq" class="transition hover:text-white">Términos</a></li>
                        </ul>
                    </div>
                </div>
                <div class="mt-12 border-t border-white/10 pt-8 text-xs text-slate-500">
                    <!-- Descargo de responsabilidad -->
                    <p class="leading-relaxed text-slate-400">
                        CrediData es una plataforma privada e independiente. No es un sitio del gobierno ecuatoriano ni está afiliada a ninguna entidad oficial. Durante esta etapa de desarrollo, los datos mostrados son simulados.
                    </p>

                    <!-- Información de derechos y créditos -->
                    <div class="mt-6 flex flex-col items-center justify-between gap-3 text-sm sm:flex-row">
                        <p class="text-slate-400">
                            &copy; {{ date('Y') }} <span class="font-medium text-slate-300">CrediData</span>. Todos los derechos reservados.
                        </p>

                        <p class="inline-flex items-center gap-1 font-medium text-slate-400">
                            <span>Desarrollado por</span>
                            <a
                                href="https://softecsa.com"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-slate-300 underline decoration-slate-600 underline-offset-4 transition-colors hover:text-white hover:decoration-white focus:outline-none focus:ring-2 focus:ring-white/20 focus:ring-offset-2 focus:ring-offset-slate-900 rounded-sm"
                            >
                                Softecapps S.A.S.
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </footer>

        @livewireScripts
    </body>
</html>
