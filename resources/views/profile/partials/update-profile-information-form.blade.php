<section>
    <header>
        <h2 id="account-heading" class="ui-section-title">
            Datos de la cuenta
        </h2>

        <p class="mt-1 text-sm leading-6 text-slate-600">
            Actualizá tu nombre. El correo registrado se muestra como dato informativo.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Nombre" />
            <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name', $user->name)" required autofocus autocomplete="name" :aria-invalid="$errors->has('name') ? 'true' : null" :aria-describedby="$errors->has('name') ? 'name-error' : null" />
            <x-input-error id="name-error" class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" type="email" class="mt-1 bg-slate-50" :value="$user->email" readonly aria-describedby="email-help" />
            <p id="email-help" class="ui-help">Por ahora, el correo no se puede modificar desde esta pantalla.</p>

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="ui-alert ui-alert--warning mt-3">Tu correo todavía no está verificado.</p>
                <button form="send-verification" class="mt-2 min-h-11 text-sm font-semibold text-[#3155d9] underline underline-offset-4 hover:text-[#2647c2]">
                    Enviar nuevo enlace de verificación
                </button>
            @endif

            @if (session('status') === 'verification-link-sent')
                <p role="status" class="ui-alert ui-alert--success mt-3">Se envió un nuevo enlace a tu correo electrónico.</p>
            @elseif (session('status') === 'verification-link-cooldown')
                <p role="status" class="ui-alert ui-alert--warning mt-3">Ya se envió un enlace hace poco. Esperá a que termine el tiempo indicado antes de solicitar otro.</p>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Guardar datos</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    role="status"
                    class="ui-alert ui-alert--success"
                >Tus datos se guardaron correctamente.</p>
            @endif
        </div>
    </form>
</section>
