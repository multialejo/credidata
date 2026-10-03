@props(['detail'])

<x-modal name="client-detail" maxWidth="2xl" titleId="client-detail-title" descriptionId="client-detail-description">
    @if($detail)
        <div class="p-6 sm:p-7">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-5">
                <div>
                    <p class="ui-eyebrow">Detalle del cliente</p>
                    <h2 id="client-detail-title" class="mt-1 text-xl font-bold text-[#14213d]">{{ $detail->usuario->nombre ?? 'Cliente' }}</h2>
                    <p id="client-detail-description" class="mt-1 text-sm text-slate-600">{{ $detail->usuario->email ?? 'Sin correo registrado' }}</p>
                </div>
                <button type="button" wire:click="cerrarDetalle" x-on:click="$dispatch('close')" class="ui-secondary-button min-h-11 min-w-11 px-3" aria-label="Cerrar detalle">Cerrar</button>
            </div>

            <div class="mt-5 space-y-4">
                <section class="rounded-2xl border border-slate-200 bg-white p-4" aria-labelledby="client-api-keys-title">
                    <h3 id="client-api-keys-title" class="ui-section-title mb-3 text-base">API Keys</h3>
                    @if($detail->apiKeys->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="ui-data-table__table ui-data-table__table--compact">
                                <caption class="sr-only">API Keys del cliente {{ $detail->usuario->nombre }}</caption>
                                <thead><tr><th scope="col">Aplicativo</th><th scope="col">Estado</th><th scope="col">Prefijo</th><th scope="col">Último uso</th><th scope="col">Permisos</th><th scope="col">IPs permitidas</th></tr></thead>
                                <tbody>
                                    @foreach($detail->apiKeys as $apiKey)
                                        <tr wire:key="cliente-{{ $detail->id }}-api-key-{{ $apiKey->id }}"><th scope="row" class="text-left font-semibold">{{ $apiKey->nombre }}</th><td>{{ $apiKey->revocada ? 'Revocada' : 'Activa' }}</td><td><code class="font-mono text-xs">cd_sk_{{ $apiKey->prefijo }}</code></td><td class="whitespace-nowrap">{{ $apiKey->ultimo_uso_en?->format('d/m/Y H:i') ?? 'Nunca' }}</td><td>{{ implode(', ', $apiKey->alcance ?? []) ?: '—' }}</td><td>{{ implode(', ', $apiKey->ips_permitidas ?? []) ?: 'Todas' }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-slate-500">Sin API Keys generadas.</p>
                    @endif
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4" aria-labelledby="client-consultas-title">
                    <h3 id="client-consultas-title" class="ui-section-title mb-3 text-base">Últimas consultas</h3>
                    @if($detail->consultas->isNotEmpty())
                        <table class="ui-data-table__table ui-data-table__table--compact"><thead><tr><th scope="col">Fecha</th><th scope="col">Tipo</th><th scope="col">Identificador</th><th scope="col">Créditos</th></tr></thead><tbody>
                            @foreach($detail->consultas as $c)<tr><td class="whitespace-nowrap">{{ $c->fecha->format('d/m/Y H:i') }}</td><td><span class="ui-table-badge bg-slate-100 uppercase text-slate-700">{{ $c->tipo }}</span></td><td class="font-mono text-sm">{{ $c->identificador }}</td><td class="font-medium tabular-nums text-rose-700">-{{ $c->creditos_gastados }}</td></tr>@endforeach
                        </tbody></table>
                    @else
                        <p class="text-sm text-slate-500">Sin consultas.</p>
                    @endif
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4" aria-labelledby="client-recargas-title">
                    <h3 id="client-recargas-title" class="ui-section-title mb-3 text-base">Últimas recargas</h3>
                    @if($detail->recargas->isNotEmpty())
                        <table class="ui-data-table__table ui-data-table__table--compact"><thead><tr><th scope="col">Fecha</th><th scope="col">Método</th><th scope="col">Monto USD</th><th scope="col">Créditos</th><th scope="col">Estado</th></tr></thead><tbody>
                            @foreach($detail->recargas as $r)
                                <tr><td class="whitespace-nowrap">{{ $r->fecha->format('d/m/Y H:i') }}</td><td>{{ $r->metodo }}</td><td>{{ number_format($r->monto_usd, 2) }}</td><td class="font-medium tabular-nums">{{ $r->creditos_obtenidos }}</td><td><span @class([
                                    'ui-table-badge',
                                    'bg-amber-100 text-amber-800' => $r->estado->color() === 'yellow',
                                    'bg-emerald-100 text-emerald-800' => $r->estado->color() === 'green',
                                    'bg-rose-100 text-rose-800' => $r->estado->color() === 'red',
                                    'bg-orange-100 text-orange-800' => $r->estado->color() === 'orange',
                                    'bg-slate-100 text-slate-700' => ! in_array($r->estado->color(), ['yellow', 'green', 'red', 'orange'], true),
                                ])>{{ $r->estado->label() }}</span></td></tr>
                            @endforeach
                        </tbody></table>
                    @else
                        <p class="text-sm text-slate-500">Sin recargas.</p>
                    @endif
                </section>
            </div>
        </div>
    @endif
</x-modal>
