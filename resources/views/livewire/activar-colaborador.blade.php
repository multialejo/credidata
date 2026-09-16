<div class="max-w-2xl bg-white p-6 shadow-sm sm:rounded-lg">
    <h1 class="text-xl font-semibold text-gray-900">Programa de colaboradores</h1>
    @if(session('status')) <p class="mt-3 rounded bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</p> @endif
    @if($colaborador)
        <p class="mt-4 text-sm text-gray-700">Perfil activo desde {{ $colaborador->terminos_aceptados_en?->format('d/m/Y H:i') }}. Términos: {{ $colaborador->terminos_version }}.</p>
        <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg bg-indigo-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-indigo-700">Créditos obtenidos</p>
                <p class="mt-1 text-2xl font-bold text-indigo-900">{{ number_format($colaborador->creditos_acreditados, 0) }}</p>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-600">Aportes aprobados</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($colaborador->aportes_aprobados, 0) }}</p>
            </div>
            <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-600">Total de aportes</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($colaborador->total_aportes, 0) }}</p>
            </div>
        </div>
        <a class="mt-4 inline-block text-indigo-600" href="{{ route('dashboard.aportes.nuevo') }}">Enviar un aporte</a>
    @else
        <p class="mt-4 text-sm text-gray-700">{{ $terminos }}</p>
        <p class="mt-2 text-xs text-gray-500">Versión {{ $version }}</p>
        <form wire:submit="activar" class="mt-5 space-y-3"><label class="flex gap-2 text-sm"><input wire:model="aceptaTerminos" type="checkbox"> Acepto los términos provisionales.</label>@error('aceptaTerminos') <p class="text-sm text-red-600">{{ $message }}</p> @enderror <x-primary-button>Activar colaboración</x-primary-button></form>
    @endif
</div>
