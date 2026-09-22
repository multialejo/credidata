<x-guest-layout>
    <div class="mb-6">
        <h1 class="ui-page-title">Confirma tu contraseña</h1>
    </div>
    <div class="mb-4 text-sm text-slate-600">
        Esta es un área segura de la aplicación. Por favor confirmá tu contraseña para continuar.
    </div>

    @if ($errors->any())
        <div class="ui-alert ui-alert--danger mb-4">
            <ul class="space-y-1 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mt-4">
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" name="password" type="password" class="mt-1" required autocomplete="current-password" autofocus />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Confirmar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
