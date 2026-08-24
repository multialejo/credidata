<x-guest-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Recarga PayPal
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @switch($status ?? null)
                    @case('completada')
                        <div class="text-center">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900">Recarga acreditada</h3>
                            <p class="mt-2 text-sm text-gray-600">
                                Tu recarga por
                                <span class="font-semibold text-green-600">
                                    {{ number_format((int) $recarga->creditos_obtenidos, 0) }}
                                </span>
                                créditos fue procesada con éxito.
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                Orden: {{ $recarga->referencia_externa }}
                            </p>
                            <a href="{{ route('dashboard') }}" class="mt-6 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded">
                                Volver al Panel de Saldo
                                <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>
                    @break

                    @case('pendiente')
                        <div class="text-center">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900">Pendiente</h3>
                            <p class="mt-2 text-sm text-gray-600">
                                Tu orden <span class="font-mono">{{ $recarga->referencia_externa }}</span>
                                está pendiente de acreditación.
                            </p>
                            @auth
                                <p class="mt-2 text-sm text-gray-600">
                                    Si acabas de aprobar el pago en PayPal, recargá esta página en unos segundos.
                                </p>
                            @else
                                <p class="mt-2 text-sm text-gray-600">
                                    Iniciá sesión con la cuenta que usaste para la recarga para acreditar tu saldo.
                                </p>
                                <a href="{{ route('login') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                    Ir al login
                                </a>
                            @endauth
                        </div>
                    @break

                    @case('fallida')
                        <div class="text-center">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900">Pago no completado</h3>
                            <p class="mt-2 text-sm text-gray-600">
                                Tu orden no pudo ser procesada por PayPal. Podés reintentar la recarga.
                            </p>
                            <a href="{{ route('dashboard') }}" class="mt-6 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded">
                                Volver al Panel de Saldo
                                <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>
                    @break

                    @case('rechazada')
                        <div class="text-center">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-orange-100">
                                <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900">Recarga rechazada</h3>
                            <p class="mt-2 text-sm text-gray-600">
                                Tu recarga fue rechazada. Contactá a soporte para más información.
                            </p>
                            <a href="{{ route('dashboard') }}" class="mt-6 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded">
                                Volver al Panel de Saldo
                                <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>
                    @break

                    @default
                        <div class="text-center">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100">
                                <svg class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900">Orden no encontrada</h3>
                            <p class="mt-2 text-sm text-gray-600">
                                No pudimos encontrar la orden asociada a esta URL.
                            </p>
                            <a href="{{ route('dashboard') }}" class="mt-6 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded">
                                Volver al Panel de Saldo
                                <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>
                @endswitch

            </div>
        </div>
    </div>
</x-guest-layout>
