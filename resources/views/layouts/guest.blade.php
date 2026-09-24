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
    </head>
    <body class="font-sans text-[#14213d] antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center bg-[#f7f5ef] px-4 py-12">
            <a href="/" class="flex items-center gap-2.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#3155d9] focus:ring-offset-2 focus:ring-offset-[#f7f5ef]">
                <x-application-logo class="h-10 w-[49px] text-[#14213d]" />
                <span class="text-xl font-extrabold tracking-tight text-[#14213d]">CrediData</span>
            </a>

            <div class="mt-8 w-full max-w-md">
                <div class="ui-card p-8 sm:p-10">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @livewireScripts
    </body>
</html>
