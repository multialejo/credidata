<x-guest-layout>
    <div class="text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-700" aria-hidden="true">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6" />
            </svg>
        </div>

        <h1 class="ui-page-title mt-5">Correo verificado</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">
            Tu dirección de correo ya está confirmada. Inicia sesión para entrar a tu cuenta de CrediData.
        </p>

        <a href="{{ route('login') }}" class="ui-primary-button mt-6 w-full justify-center">
            Ir a iniciar sesión
        </a>
    </div>
</x-guest-layout>
