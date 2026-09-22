<x-page-shell max-width="3xl">
    <x-page-header eyebrow="Colaboradores" title="Enviar aporte" description="Comparte un dato público para ayudar a mejorar las consultas de Credidata." />
    <section class="ui-card p-5 sm:p-7">
    @if($resultado) <x-alert variant="{{ $resultado === 'aprobado' ? 'success' : 'warning' }}" class="mb-6">{{ $resultado === 'aprobado' ? "El dato fue aplicado y se acreditaron {$recompensaAcreditada} créditos a tu cuenta." : 'El dato ya existe y quedó pendiente de revisión.' }}</x-alert> @endif
    <form wire:submit="enviar" class="space-y-5">
        <div><x-input-label for="identificador" value="Cédula o RUC"/><x-text-input id="identificador" wire:model="identificador" class="mt-1"/>@error('identificador')<p class="ui-error" role="alert">{{ $message }}</p>@enderror</div>
        <div><x-input-label for="tipoDato" value="Tipo de dato"/><select id="tipoDato" wire:model="tipoDato" class="ui-input mt-1 w-full"><option value="telefono">Teléfono</option><option value="email">Correo electrónico</option><option value="direccion">Dirección</option></select></div>
        <div><x-input-label for="valor" value="Valor"/><x-text-input id="valor" wire:model="valor" class="mt-1"/>@error('valor')<p class="ui-error" role="alert">{{ $message }}</p>@enderror</div>
        <x-primary-button wire:loading.attr="disabled" wire:target="enviar">
            <span wire:loading.remove wire:target="enviar">Enviar aporte</span>
            <span wire:loading wire:target="enviar">Enviando...</span>
        </x-primary-button>
    </form>
    </section>
</x-page-shell>
