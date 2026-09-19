<x-guest-layout>
    <div class="mb-4 text-sm text-slate-600">
        Antes de continuar, por favor verificá tu dirección de correo electrónico haciendo clic en el enlace que te enviamos. Si no lo recibiste, con gusto te enviaremos otro.
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-emerald-600">
            Se envió un nuevo enlace de verificación a tu correo electrónico.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                Reenviar correo de verificación
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="underline text-sm text-slate-600 hover:text-slate-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#3155d9]">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-guest-layout>
