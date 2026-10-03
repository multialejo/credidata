<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f7f5ef">
        <title>Página no encontrada | {{ config('app.name', 'CrediData') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-[#f7f5ef] font-sans text-[#14213d] antialiased">
        <main class="relative isolate flex min-h-screen flex-col overflow-hidden">
            <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
                <div class="absolute -right-32 -top-40 h-96 w-96 rounded-full bg-[#3155d9]/10 blur-3xl"></div>
                <div class="absolute -bottom-48 -left-32 h-96 w-96 rounded-full bg-[#3155d9]/10 blur-3xl"></div>
                <div class="absolute inset-0 opacity-[0.28] [background-image:radial-gradient(#14213d_1px,transparent_1px)] [background-size:28px_28px]"></div>
            </div>

            <header class="mx-auto flex w-full max-w-7xl items-center justify-between px-5 py-6 sm:px-8">
                <a href="{{ url('/') }}" aria-label="CrediData, ir al inicio" class="flex min-h-11 items-center gap-2.5 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-[#3155d9] focus-visible:ring-offset-4 focus-visible:ring-offset-[#f7f5ef]">
                    <x-application-logo class="h-9 w-9 text-[#3155d9]" />
                    <span class="text-xl font-extrabold tracking-tight">CrediData</span>
                </a>
                <span class="hidden text-sm font-medium text-slate-500 sm:block">Consultas claras, sin complicaciones</span>
            </header>

            <section class="mx-auto flex w-full max-w-7xl flex-1 items-center px-5 py-12 sm:px-8 sm:py-16" aria-labelledby="error-title">
                <div class="grid w-full items-center gap-12 lg:grid-cols-[1.1fr_0.9fr] lg:gap-16">
                    <div class="max-w-2xl">
                        <p class="font-mono text-sm font-semibold tracking-[0.22em] text-[#3155d9]">ERROR 404</p>
                        <h1 id="error-title" class="mt-5 text-4xl font-extrabold leading-tight tracking-tight text-[#14213d] sm:text-5xl lg:text-6xl">
                            Esta página no aparece en el mapa.
                        </h1>
                        <p class="mt-5 max-w-xl text-base leading-7 text-slate-600 sm:text-lg">
                            Puede que el enlace haya cambiado o que la dirección esté mal escrita. Vuelve a un lugar conocido y sigue desde ahí.
                        </p>

                        @auth
                            <a href="{{ url('/dashboard') }}" class="mt-8 inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-[#3155d9] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#2647c2] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#3155d9] focus-visible:ring-offset-2 focus-visible:ring-offset-[#f7f5ef]">
                                Ir al panel
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m-6-6 6 6-6 6" />
                                </svg>
                            </a>
                        @else
                            <a href="{{ url('/') }}" class="mt-8 inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-[#3155d9] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#2647c2] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#3155d9] focus-visible:ring-offset-2 focus-visible:ring-offset-[#f7f5ef]">
                                Volver al inicio
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m-6-6 6 6-6 6" />
                                </svg>
                            </a>
                        @endauth
                    </div>

                    <div class="relative mx-auto w-full max-w-md lg:justify-self-end" aria-hidden="true">
                        <div class="relative aspect-square overflow-hidden rounded-[2rem] border border-[#e2e8f0] bg-white/80 p-6 shadow-[0_24px_60px_-32px_rgba(20,33,61,0.4)] sm:p-8">
                            <div class="absolute inset-0 opacity-60 [background-image:linear-gradient(to_right,#e2e8f0_1px,transparent_1px),linear-gradient(to_bottom,#e2e8f0_1px,transparent_1px)] [background-size:36px_36px]"></div>
                            <svg class="relative h-full w-full" viewBox="0 0 360 360" fill="none">
                                <path d="M64 78h232v204H64z" fill="#f7f5ef" stroke="#e2e8f0" stroke-width="2" stroke-dasharray="7 8" />
                                <path d="M105 226c29-48 55-47 78-16 21 28 42 17 72-49" stroke="#3155d9" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="105" cy="226" r="13" fill="#fff" stroke="#3155d9" stroke-width="7" />
                                <circle cx="255" cy="161" r="13" fill="#fff" stroke="#3155d9" stroke-width="7" />
                                <path d="M243 148l24 26m0-26-24 26" stroke="#14213d" stroke-width="4" stroke-linecap="round" />
                                <rect x="130" y="105" width="100" height="48" rx="16" fill="#e8edf9" />
                                <text x="180" y="138" text-anchor="middle" fill="#3155d9" font-family="Figtree, sans-serif" font-size="26" font-weight="800">404</text>
                                <path d="M83 259h50" stroke="#cbd5e1" stroke-width="5" stroke-linecap="round" />
                                <path d="M83 275h31" stroke="#cbd5e1" stroke-width="5" stroke-linecap="round" />
                            </svg>
                            <div class="absolute bottom-6 left-6 rounded-lg border border-[#e2e8f0] bg-white px-3 py-2 text-xs font-semibold text-slate-500 shadow-sm sm:bottom-8 sm:left-8">
                                Ruta no encontrada
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <footer class="mx-auto w-full max-w-7xl px-5 pb-6 text-xs text-slate-500 sm:px-8">
                <p>&copy; {{ date('Y') }} CrediData</p>
            </footer>
        </main>
    </body>
</html>
