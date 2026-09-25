<x-page-shell max-width="6xl">
    @if(session('status'))
        <x-alert variant="success">{{ session('status') }}</x-alert>
    @endif

    @if($colaborador)
        <header class="flex flex-col gap-5 rounded-2xl bg-[#14213d] p-6 text-white shadow-[0_20px_45px_-25px_rgba(20,33,61,0.8)] sm:flex-row sm:items-end sm:justify-between sm:p-8">
            <div class="max-w-2xl">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Tu actividad de colaboración</h1>
                    <span @class([
                        'rounded-full px-3 py-1 text-xs font-semibold',
                        'bg-emerald-400/15 text-emerald-200' => $colaborador->estado_colaborador === 'activo',
                        'bg-amber-300/15 text-amber-200' => $colaborador->estado_colaborador !== 'activo',
                    ])>{{ $colaborador->estado_colaborador === 'activo' ? 'Activo' : 'Suspendido' }}</span>
                </div>
                <p class="mt-2 text-sm leading-6 text-slate-300">Consulta el avance de tus aportes, revisa tus créditos y continúa ayudando a mejorar los datos de Credidata.</p>
            </div>
            @if($colaborador->estado_colaborador === 'activo')
                <a class="ui-primary-button shrink-0 bg-white text-[#14213d] hover:bg-slate-100 focus:ring-white" href="{{ route('dashboard.aportes.nuevo') }}">
                    Enviar aporte
                    <span class="ml-2" aria-hidden="true">→</span>
                </a>
            @endif
        </header>

        <section aria-label="Resumen de colaboración" class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            <section class="ui-card p-5 sm:p-6" aria-labelledby="credits-heading">
                <h2 id="credits-heading" class="text-sm font-semibold text-slate-600">Créditos acreditados</h2>
                <p class="mt-2 text-4xl font-bold tracking-tight tabular-nums text-[#14213d]">{{ number_format($colaborador->creditos_acreditados) }}</p>
                <p class="mt-1 text-sm text-slate-500">Sumados al saldo de tu cuenta.</p>
            </section>

            <section class="ui-card p-5 sm:p-6" aria-labelledby="status-heading">
                <h2 id="status-heading" class="ui-section-title">Estado de tus aportes</h2>
                <dl class="mt-4 divide-y divide-slate-100 text-sm">
                    <div class="flex items-center justify-between gap-3 py-3 first:pt-0">
                        <dt class="text-slate-600">Pendientes de revisión</dt>
                        <dd class="font-semibold tabular-nums text-[#14213d]">{{ number_format($pendientes) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-3">
                        <dt class="text-slate-600">Aprobados</dt>
                        <dd class="font-semibold tabular-nums text-emerald-700">{{ number_format($colaborador->aportes_aprobados) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-3 last:pb-0">
                        <dt class="text-slate-600">Rechazados</dt>
                        <dd class="font-semibold tabular-nums text-slate-700">{{ number_format($colaborador->aportes_rechazados) }}</dd>
                    </div>
                </dl>
            </section>

            <section class="ui-card p-5 sm:p-6 md:col-span-2 xl:col-span-1" aria-labelledby="daily-limit-heading">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="daily-limit-heading" class="text-sm font-semibold text-[#14213d]">Límite diario</h2>
                        <p class="mt-1 text-sm leading-5 text-slate-600">Aportes enviados hoy</p>
                    </div>
                    <p class="shrink-0 text-lg font-bold tabular-nums text-[#14213d]">{{ number_format(min($aportesHoy, $limiteDiario)) }}<span class="font-medium text-slate-500"> / {{ number_format($limiteDiario) }}</span></p>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="Uso del límite diario" aria-valuemin="0" aria-valuemax="{{ max(1, $limiteDiario) }}" aria-valuenow="{{ min($aportesHoy, $limiteDiario) }}">
                    <div class="h-full rounded-full bg-[#3155d9] transition-[width]" style="width: {{ $limiteDiario > 0 ? min(100, ($aportesHoy / $limiteDiario) * 100) : 100 }}%"></div>
                </div>
                <p class="mt-2 text-xs leading-5 text-slate-500">El límite se renueva al comenzar el día.</p>
            </section>
        </section>
    @else
        <x-page-header eyebrow="Colaboradores" title="Programa de colaboradores" description="Aporta datos públicos, ayuda a mejorar Credidata y recibe créditos cuando tus aportes sean aprobados." />
        <section class="ui-card p-5 sm:p-7" aria-labelledby="terms-heading">
            <h2 id="terms-heading" class="ui-section-title">Antes de empezar</h2>
            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700">{{ $terminos }}</div>
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
        </section>
    @endif
</x-page-shell>
