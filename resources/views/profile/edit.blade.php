<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Tu perfil" description="Actualizá tus datos y protegé el acceso a tu cuenta." class="mb-0" />
    </x-slot>

    <x-page-shell max-width="6xl" class="grid gap-6 lg:grid-cols-2">
        <!-- Información Personal -->
        <section aria-labelledby="account-heading" class="ui-card p-5 sm:p-8">
            @include('profile.partials.update-profile-information-form')
        </section>

        <!-- Seguridad -->
        <section aria-labelledby="security-heading" class="ui-card p-5 sm:p-8">
            @include('profile.partials.update-password-form')
        </section>

        <!-- Sesión activa -->
        <section aria-labelledby="session-heading" class="ui-card p-5 sm:p-8 lg:col-span-2">
            <div class="max-w-xl">
                <header>
                    <h2 id="session-heading" class="ui-section-title">Sesión</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Cerrá tu sesión activa en este dispositivo.</p>
                </header>
                <form method="post" action="{{ route('logout') }}" class="mt-5">
                    @csrf
                    <x-secondary-button type="submit">Cerrar sesión</x-secondary-button>
                </form>
            </div>
        </section>

        {{-- WhatsApp contact settings --}}
        {{--
        <section aria-labelledby="whatsapp-heading" class="ui-card p-5 sm:p-8">
            <header>
                <h2 id="whatsapp-heading" class="ui-section-title">Completa tu perfil cuando quieras</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">
                    Agregá tu número de WhatsApp y, si querés, autorizá a Credidata a contactarte. Ambos datos son opcionales.
                </p>
            </header>

            <form method="post" action="{{ route('profile.whatsapp.update') }}" class="mt-6 space-y-5">
                @csrf
                @method('patch')

                <div>
                    <x-input-label for="whatsapp_number" value="Número de WhatsApp" />
                    <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                        <div class="flex min-h-11 shrink-0 items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 sm:w-44">
                            <svg aria-hidden="true" viewBox="0 0 30 20" class="h-5 w-[30px] overflow-hidden rounded-sm">
                                <rect width="30" height="10" fill="#FCD116" />
                                <rect y="10" width="30" height="5" fill="#003893" />
                                <rect y="15" width="30" height="5" fill="#CE1126" />
                            </svg>
                            <label for="whatsapp_country" class="sr-only">Código de país</label>
                            <select id="whatsapp_country" class="ui-input w-full border-0 bg-transparent py-0 pl-0 pr-6 focus:ring-0">
                                <option value="+593">Ecuador +593</option>
                            </select>
                        </div>
                        <div class="min-w-0 flex-1">
                            <x-text-input id="whatsapp_number" name="whatsapp_number" type="tel" inputmode="numeric" autocomplete="tel-national" maxlength="13" :value="old('whatsapp_number', $user->whatsapp_number ? preg_replace('/^\+593/', '', $user->whatsapp_number) : '')" placeholder="09 1234 5678" aria-describedby="whatsapp-help" />
                            <p id="whatsapp-help" class="ui-help">Podés ingresarlo con o sin el cero inicial.</p>
                            <x-input-error id="whatsapp-number-error" :messages="$errors->get('whatsapp_number')" />
                        </div>
                    </div>
                </div>

                <div>
                    <input type="hidden" name="whatsapp_consent" value="0">
                    <label for="whatsapp_consent" class="flex cursor-pointer items-start gap-3 text-sm leading-6 text-slate-700">
                        <input id="whatsapp_consent" name="whatsapp_consent" type="checkbox" value="1" @checked(old('whatsapp_consent', $user->whatsapp_consent)) class="mt-1 h-4 w-4 rounded border-slate-300 text-[#3155d9] focus:ring-[#3155d9]">
                        <span>Autorizo a Credidata a enviarme mensajes relacionados con mi cuenta por WhatsApp.</span>
                    </label>
                    <x-input-error id="whatsapp-consent-error" :messages="$errors->get('whatsapp_consent')" />
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <x-primary-button>Guardar número</x-primary-button>
                    @if (session('status') === 'profile-whatsapp-updated')
                        <p role="status" class="ui-alert ui-alert--success">Número guardado.</p>
                    @endif
                </div>
            </form>
        </section>
        --}}

        <!-- Zona de peligro -->
        <section aria-labelledby="danger-heading" class="ui-card border-rose-200 bg-rose-50/50 p-5 sm:p-8 lg:col-span-2">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="danger-heading" class="ui-section-title text-rose-900">Eliminar cuenta</h2>
                    <p class="mt-1 max-w-xl text-sm leading-6">
                        Esta acción es irreversible. Se eliminarán tu cuenta y sus datos asociados; descargá antes cualquier información que quieras conservar.
                    </p>
                </div>
                <div class="shrink-0">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </section>
    </x-page-shell>
</x-app-layout>
