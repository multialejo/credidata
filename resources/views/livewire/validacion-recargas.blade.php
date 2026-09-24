<x-page-shell max-width="5xl">
    <x-page-header eyebrow="Administración" title="Acreditar transferencia" description="Registra y valida manualmente una transferencia bancaria de un cliente." />
    @if (session('status'))
        <x-alert variant="success">{{ session('status') }}</x-alert>
    @endif
    <section class="ui-card p-5 sm:p-7">
        @if($puedeAcreditar)
            <form wire:submit="acreditar" enctype="multipart/form-data" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="clienteEmail" value="Email del cliente" />
                    <x-text-input id="clienteEmail" wire:model="clienteEmail" type="email" class="mt-1 block w-full" />
                    @error('clienteEmail') <p class="ui-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-input-label for="montoUsd" value="Monto USD" />
                    <x-text-input id="montoUsd" wire:model="montoUsd" type="number" min="0.01" step="0.01" class="mt-1 block w-full" />
                    <p class="ui-help">Créditos calculados: {{ $this->creditosCalculados }}</p>
                    @error('montoUsd') <p class="ui-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-input-label for="referenciaBancaria" value="Referencia bancaria" />
                    <x-text-input id="referenciaBancaria" wire:model="referenciaBancaria" class="mt-1 block w-full" />
                    @error('referenciaBancaria') <p class="ui-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-input-label for="comprobante" value="Comprobante (JPG, PNG o PDF)" />
                    <input id="comprobante" wire:model="comprobante" type="file" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full text-sm" />
                    @error('comprobante') <p class="ui-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="motivo" value="Motivo" />
                    <textarea id="motivo" wire:model="motivo" rows="2" class="ui-input mt-1 block w-full"></textarea>
                    @error('motivo') <p class="ui-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <x-primary-button wire:loading.attr="disabled">Acreditar transferencia</x-primary-button>
                </div>
            </form>
        @else
            <p class="ui-alert ui-alert--warning mt-4">Solo admin o support pueden acreditar transferencias.</p>
        @endif
    </section>
</x-page-shell>
