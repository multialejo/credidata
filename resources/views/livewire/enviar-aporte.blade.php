<div class="max-w-2xl bg-white p-6 shadow-sm sm:rounded-lg">
    <h1 class="text-xl font-semibold">Enviar aporte</h1>
    @if($resultado) <p class="mt-3 rounded p-3 text-sm {{ $resultado === 'aprobado' ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800' }}">{{ $resultado === 'aprobado' ? 'El dato fue aplicado y tu recompensa fue acreditada.' : 'El dato ya existe y quedó pendiente de revisión.' }}</p> @endif
    <form wire:submit="enviar" class="mt-5 space-y-4">
        <div><x-input-label for="identificador" value="Cédula o RUC"/><x-text-input id="identificador" wire:model="identificador" class="mt-1 block w-full"/>@error('identificador')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div><x-input-label for="tipoDato" value="Tipo de dato"/><select id="tipoDato" wire:model="tipoDato" class="mt-1 block w-full rounded-md border-gray-300"><option value="telefono">Teléfono</option><option value="email">Email</option><option value="direccion">Dirección</option></select></div>
        <div><x-input-label for="valor" value="Valor"/><x-text-input id="valor" wire:model="valor" class="mt-1 block w-full"/>@error('valor')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <x-primary-button>Enviar aporte</x-primary-button>
    </form>
</div>
