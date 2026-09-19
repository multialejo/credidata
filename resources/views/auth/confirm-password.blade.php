<x-guest-layout>
    <div class="mb-4 text-sm text-slate-600">
        Esta es un área segura de la aplicación. Por favor confirmá tu contraseña para continuar.
    </div>

    @if ($errors->any())
        <div class="mb-4">
            <ul class="text-sm text-red-600 space-y-1">
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
            <x-text-input id="password" name="password" type="password" class="block mt-1 w-full" required autocomplete="current-password" autofocus />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Confirmar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
