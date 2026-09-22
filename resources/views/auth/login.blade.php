<x-guest-layout>
    <div class="mb-6">
        <h1 class="ui-page-title">Iniciar sesión</h1>
    </div>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-[#3155d9] shadow-sm focus:ring-[#3155d9]" name="remember">
                <span class="ms-2 text-sm text-slate-600">Recordarme</span>
            </label>
        </div>

        <div class="mt-6 flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-end">
            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-slate-600 underline underline-offset-2 hover:text-[#3155d9]" href="{{ route('password.request') }}">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif

            <x-primary-button>
                Iniciar sesión
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
