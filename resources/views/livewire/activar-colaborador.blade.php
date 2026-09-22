<x-page-shell max-width="3xl">
    <x-page-header eyebrow="Colaboradores" title="Programa de colaboradores" description="Aporta datos públicos, ayuda a mejorar Credidata y recibe créditos cuando tus aportes sean aprobados." />
    <section class="ui-card p-5 sm:p-7">
    @if(session('status')) <x-alert variant="success" class="mb-6">{{ session('status') }}</x-alert> @endif
    @if($colaborador)
        <p class="text-sm leading-6 text-slate-600">Perfil activo desde {{ $colaborador->terminos_aceptados_en?->format('d/m/Y H:i') }}. Términos: {{ $colaborador->terminos_version }}.</p>
        <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl bg-[#e8edf9] p-4">
                <p class="ui-eyebrow text-[#3155d9]">Créditos obtenidos</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-[#14213d]">{{ number_format($colaborador->creditos_acreditados, 0) }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <p class="ui-eyebrow">Aportes aprobados</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-[#14213d]">{{ number_format($colaborador->aportes_aprobados, 0) }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-4">
                <p class="ui-eyebrow">Total de aportes</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-[#14213d]">{{ number_format($colaborador->total_aportes, 0) }}</p>
            </div>
        </div>
        <a class="ui-primary-button mt-6" href="{{ route('dashboard.aportes.nuevo') }}">Enviar un aporte</a>
    @else
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700">{{ $terminos }}</div>
        <p class="ui-help">Versión {{ $version }}</p>
        <form wire:submit="activar" class="mt-6 space-y-4">
            <label class="flex cursor-pointer items-start gap-3 text-sm leading-6 text-slate-700">
                <input wire:model="aceptaTerminos" type="checkbox" class="mt-1 rounded border-slate-300 text-[#3155d9] focus:ring-[#3155d9]">
                <span>Acepto los términos provisionales del programa de colaboradores.</span>
            </label>
            @error('aceptaTerminos') <p class="ui-error" role="alert">{{ $message }}</p> @enderror
            <x-primary-button wire:loading.attr="disabled" wire:target="activar">
                <span wire:loading.remove wire:target="activar">Activar colaboración</span>
                <span wire:loading wire:target="activar">Activando...</span>
            </x-primary-button>
        </form>
    @endif
    </section>
</x-page-shell>
