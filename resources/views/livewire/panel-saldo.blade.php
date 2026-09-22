<x-page-shell max-width="7xl">
    <x-page-header eyebrow="Área de cliente" title="Tu cuenta, en orden." description="Consulta tu saldo y gestiona tus créditos." />

        <section class="grid gap-6 lg:grid-cols-[1.35fr_0.65fr]" aria-label="Resumen de cuenta">
            <div class="relative overflow-hidden rounded-2xl bg-[#14213d] p-6 text-white shadow-[0_20px_45px_-25px_rgba(20,33,61,0.8)] sm:p-8">
                <div class="absolute -right-16 -top-20 h-56 w-56 rounded-full border-[28px] border-white/5"></div>
                <div class="relative">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-slate-300">Saldo disponible</p>
                            <p class="mt-4 text-4xl font-bold tracking-tight tabular-nums sm:text-5xl">{{ number_format($saldo, 0) }}</p>
                            <p class="mt-1 text-sm text-slate-300">créditos para tus consultas</p>
                        </div>
                        <span class="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-semibold text-emerald-200">Activo</span>
                    </div>
                    <a href="{{ route('dashboard.recargas') }}" class="ui-primary-button mt-8 bg-white text-[#14213d] hover:bg-slate-100 focus:ring-white">
                        Recargar créditos
                        <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6 6 6-6 6" /></svg>
                    </a>
                </div>
            </div>

            <div class="ui-card flex flex-col justify-between p-6">
                <div>
                    <p class="ui-eyebrow">Acceso rápido</p>
                    <h2 class="mt-3 text-lg font-bold text-[#14213d]">¿Necesitas consultar datos?</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Usa tu API Key para integrar Credidata directamente con tus sistemas.</p>
                </div>
                <a href="{{ route('dashboard.api-key') }}" class="mt-6 inline-flex items-center text-sm font-semibold text-[#3155d9] hover:text-[#2647c2] focus:outline-none focus:underline">
                    Ver configuración de API
                    <span aria-hidden="true" class="ml-2">→</span>
                </a>
            </div>
        </section>

        <section class="ui-card p-5 sm:p-6" aria-labelledby="movimientos-title">
            <div class="flex flex-col gap-2 border-b border-slate-100 pb-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="ui-eyebrow">Actividad reciente</p>
                    <h2 id="movimientos-title" class="mt-1 text-xl font-bold tracking-tight text-[#14213d]">Últimos movimientos</h2>
                </div>
                <a href="{{ route('dashboard.consultas') }}" class="text-sm font-semibold text-[#3155d9] hover:text-[#2647c2] focus:outline-none focus:underline">Ver historial completo</a>
            </div>

            @if(count($ultimosMovimientos) > 0)
                <ul class="divide-y divide-slate-100" role="list">
                    @foreach($ultimosMovimientos as $mov)
                        <li class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-center gap-3">
                                @if($mov['monto'] > 0)
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700" aria-hidden="true">
                                @else
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500" aria-hidden="true">
                                @endif
                                    @if($mov['monto'] > 0)
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    @else
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 12h14" /></svg>
                                    @endif
                                    </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-[#14213d]">{{ $mov['descripcion'] }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $mov['fecha']->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 pl-12 sm:pl-0">
                                @if(($mov['tipo'] ?? null) === 'recarga')
                                    @php $status = $mov['estado'] ?? null; @endphp
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $status === 'completada' ? 'bg-emerald-50 text-emerald-700' : ($status === 'fallida' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">{{ ucfirst($status ?? 'Pendiente') }}</span>
                                @elseif(($mov['tipo'] ?? null) === 'colaboracion')
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Colaboración</span>
                                @endif
                                <span class="text-sm font-bold {{ $mov['monto'] > 0 ? 'text-emerald-600' : 'text-slate-700' }}">{{ $mov['monto'] > 0 ? '+' : '' }}{{ number_format($mov['monto'], 0) }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="ui-data-table__empty">
                    <p class="text-sm font-semibold text-slate-700">Aún no tienes movimientos.</p>
                    <p class="mt-1 text-sm text-slate-500">Cuando uses o recargues créditos, aparecerán aquí.</p>
                </div>
            @endif
        </section>
</x-page-shell>
