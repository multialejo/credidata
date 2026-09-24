<section>
    <header>
        <h2 id="password-heading" class="ui-section-title">
            Cambiar contraseña
        </h2>

        <p class="mt-1 text-sm leading-6 text-slate-600">
            Asegurate de usar una contraseña larga y segura para mantener tu cuenta protegida.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" value="Contraseña actual" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1" autocomplete="current-password" :aria-invalid="$errors->updatePassword->has('current_password') ? 'true' : null" :aria-describedby="$errors->updatePassword->has('current_password') ? 'current-password-error' : null" />
            <x-input-error id="current-password-error" :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div>
            <x-input-label for="update_password_password" value="Nueva contraseña" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1" autocomplete="new-password" :aria-invalid="$errors->updatePassword->has('password') ? 'true' : null" :aria-describedby="$errors->updatePassword->has('password') ? 'new-password-error' : null" />
            <x-input-error id="new-password-error" :messages="$errors->updatePassword->get('password')" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="Confirmar nueva contraseña" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1" autocomplete="new-password" :aria-invalid="$errors->updatePassword->has('password_confirmation') ? 'true' : null" :aria-describedby="$errors->updatePassword->has('password_confirmation') ? 'password-confirmation-error' : null" />
            <x-input-error id="password-confirmation-error" :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <x-primary-button>Guardar contraseña</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    role="status"
                    class="ui-alert ui-alert--success"
                >La contraseña se actualizó correctamente.</p>
            @endif
        </div>
    </form>
</section>
