<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>CrediData</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="font-sans antialiased bg-[#f7f5ef] min-h-screen flex flex-col items-center justify-center">
        <div class="w-full max-w-md px-6">
            <div class="flex flex-col items-center mb-8">
                <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#3155d9] text-xl font-black tracking-tight text-white mb-4">C</span>
                <h1 class="text-2xl font-bold text-[#14213d]">CrediData</h1>
                <p class="mt-2 text-sm text-slate-500 text-center">Consultas de identidad y datos empresariales de Ecuador</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/80 p-8">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="block w-full text-center px-4 py-3 bg-[#3155d9] text-white font-semibold rounded-lg hover:bg-[#2745b8] focus:outline-none focus:ring-2 focus:ring-[#3155d9] focus:ring-offset-2 transition">
                            Ir al panel
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="block w-full text-center px-4 py-3 bg-[#3155d9] text-white font-semibold rounded-lg hover:bg-[#2745b8] focus:outline-none focus:ring-2 focus:ring-[#3155d9] focus:ring-offset-2 transition">
                            Iniciar sesión
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="block w-full text-center px-4 py-3 mt-3 border border-slate-300 text-slate-700 font-semibold rounded-lg hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#3155d9] focus:ring-offset-2 transition">
                                Registrarse
                            </a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </body>
</html>
