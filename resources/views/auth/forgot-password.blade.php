<x-guest-layout>
    <div class="mb-6">
        <h1 class="ui-page-title">Restablecer contraseña</h1>
    </div>
    <div class="mb-4 text-sm text-slate-600">
        ¿Olvidaste tu contraseña? No hay problema. Ingresá tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($errors->any())
        <div class="ui-alert ui-alert--danger mb-4">
            <ul class="space-y-1 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Enviar enlace de restablecimiento
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
