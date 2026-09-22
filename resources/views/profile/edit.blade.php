<x-app-layout>
    <x-slot name="header">
        <x-page-header eyebrow="Cuenta" title="Perfil" description="Actualiza tus datos, protege tu acceso y administra tu cuenta." class="mb-0" />
    </x-slot>

    <x-page-shell max-width="3xl">
        <div class="space-y-6">
            <div class="ui-card p-5 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="ui-card p-5 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="ui-card border-rose-200 p-5 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </x-page-shell>
</x-app-layout>
