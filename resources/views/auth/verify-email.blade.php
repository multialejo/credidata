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
    @elseif (session('status') === 'verification-link-cooldown')
        <div class="ui-alert ui-alert--info mb-4">
            Ya se envió un enlace hace poco. Esperá a que termine el tiempo indicado antes de solicitar otro.
        </div>
    @endif

    <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div
            x-data="{ seconds: {{ (int) $verificationCooldown }} }"
            x-init="setInterval(() => { seconds = Math.max(0, seconds - 1) }, 1000)"
        >
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button
                    type="submit"
                    @disabled($verificationCooldown > 0)
                    x-bind:disabled="seconds > 0"
                    class="min-h-11 rounded-md text-left text-sm font-semibold text-[#3155d9] underline underline-offset-2 hover:text-[#2647c2] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#3155d9] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:text-slate-500 disabled:no-underline"
                >
                    Reenviar correo de verificación
                </button>
            </form>
            @if ($verificationCooldown > 0)
                <p
                    x-show="seconds > 0"
                    role="status"
                    aria-live="polite"
                    class="mt-1 text-sm text-slate-600"
                >
                    Podrás solicitar otro enlace en <span x-text="seconds">{{ (int) $verificationCooldown }}</span> segundos.
                </p>
            @endif
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-semibold text-slate-600 underline underline-offset-2 hover:text-[#3155d9]">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-guest-layout>
