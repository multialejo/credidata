<section>
    <x-secondary-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="border-rose-300 text-rose-700 hover:border-rose-400 hover:bg-rose-50 focus:ring-rose-500"
    >
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="mr-2 h-4 w-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M9.75 6.75V4.5h4.5v2.25m-8.25 0 .75 13.5h9l.75-13.5M10.5 10.5v6m3-6v6" />
        </svg>
        Eliminar mi cuenta
    </x-secondary-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 id="user-deletion-title" class="ui-section-title">
                ¿Seguro que querés eliminar tu cuenta?
            </h2>

            <p id="user-deletion-description" class="mt-1 text-sm leading-6 text-slate-600">
                Una vez que elimines tu cuenta, todos sus recursos y datos se borrarán permanentemente. Ingresá tu contraseña para confirmar.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Confirmá tu contraseña" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1"
                    :aria-invalid="$errors->userDeletion->has('password') ? 'true' : null"
                    :aria-describedby="$errors->userDeletion->has('password') ? 'user-deletion-password-error' : null"
                />

                <x-input-error id="user-deletion-password-error" :messages="$errors->userDeletion->get('password')" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Eliminar cuenta
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
