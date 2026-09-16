<div class="max-w-2xl bg-white p-6 shadow-sm sm:rounded-lg">
    <h1 class="text-xl font-semibold text-gray-900">Programa de colaboradores</h1>
    @if(session('status')) <p class="mt-3 rounded bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</p> @endif
    @if($colaborador)
        <p class="mt-4 text-sm text-gray-700">Perfil activo desde {{ $colaborador->terminos_aceptados_en?->format('d/m/Y H:i') }}. Términos: {{ $colaborador->terminos_version }}.</p>
        <a class="mt-4 inline-block text-indigo-600" href="{{ route('dashboard.aportes.nuevo') }}">Enviar un aporte</a>
    @else
        <p class="mt-4 text-sm text-gray-700">{{ $terminos }}</p>
        <p class="mt-2 text-xs text-gray-500">Versión {{ $version }}</p>
        <form wire:submit="activar" class="mt-5 space-y-3"><label class="flex gap-2 text-sm"><input wire:model="aceptaTerminos" type="checkbox"> Acepto los términos provisionales.</label>@error('aceptaTerminos') <p class="text-sm text-red-600">{{ $message }}</p> @enderror <x-primary-button>Activar colaboración</x-primary-button></form>
    @endif
</div>
