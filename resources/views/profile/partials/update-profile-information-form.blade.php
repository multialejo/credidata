<section>
    <header>
        <h2 class="ui-section-title">
            Información del perfil
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            Actualiza tu nombre y dirección de correo electrónico.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Nombre" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="ui-alert ui-alert--warning mt-3">
                        Tu dirección de correo no está verificada.

                        <button form="send-verification" class="mt-2 font-semibold underline underline-offset-2 hover:text-[#3155d9] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#3155d9]">
                            Enviar nuevo enlace de verificación.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="ui-alert ui-alert--success mt-3">
                            Se envió un nuevo enlace a tu correo electrónico.
                        </p>
                    @elseif (session('status') === 'verification-link-cooldown')
                        <p class="ui-alert ui-alert--info mt-3">
                            Ya se envió un enlace hace poco. Esperá a que termine el tiempo indicado antes de solicitar otro.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Guardar</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="ui-alert ui-alert--success"
                >Guardado.</p>
            @endif
        </div>
    </form>
</section>
