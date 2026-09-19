<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-[#f7f5ef]">
            @include('layouts.navigation')

            {{-- Main content area — offset by sidebar width on desktop --}}
            <div class="lg:pl-64">
                {{-- Page Heading --}}
                @isset($header)
                    <header class="border-b border-slate-200/80 bg-[#f7f5ef]">
                        <div class="px-4 py-7 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                {{-- Page Content --}}
                <main class="pb-12">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
