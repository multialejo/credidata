<x-guest-layout>
    <div class="mb-6">
        <h1 class="ui-page-title">Verifica tu correo</h1>
    </div>
    <div class="mb-4 text-sm text-slate-600">
        Antes de continuar, por favor verificá tu dirección de correo electrónico haciendo clic en el enlace que te enviamos. Si no lo recibiste, con gusto te enviaremos otro.
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="ui-alert ui-alert--success mb-4">
            Se envió un nuevo enlace de verificación a tu correo electrónico.
        </div>
    @endif

    <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                Reenviar correo de verificación
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-semibold text-slate-600 underline underline-offset-2 hover:text-[#3155d9]">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-guest-layout>
