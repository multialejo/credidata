<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">API Key</h3>

    @if($nuevaKey)
        <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
            <p class="text-sm font-medium text-yellow-800">Nueva API Key generada:</p>
            <p class="mt-1 font-mono text-sm break-all bg-yellow-100 p-2 rounded select-all">
                {{ $nuevaKey }}
            </p>
            <p class="mt-1 text-xs text-yellow-700">
                Guárdala en un lugar seguro. No se mostrará de nuevo.
            </p>
        </div>
    @endif

    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
        <div>
            <dt class="text-gray-500 text-xs uppercase tracking-wider">Prefijo</dt>
            <dd class="font-mono mt-1">{{ $prefijo ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500 text-xs uppercase tracking-wider">Estado</dt>
            <dd class="mt-1">
                @if($revocada)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        Revocada
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Activa
                    </span>
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-gray-500 text-xs uppercase tracking-wider">Creada</dt>
            <dd class="mt-1">{{ $creada ? $creada->format('d/m/Y H:i') : '—' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500 text-xs uppercase tracking-wider">Último uso</dt>
            <dd class="mt-1">{{ $ultimoUso ? $ultimoUso->format('d/m/Y H:i') : 'Nunca' }}</dd>
        </div>
    </dl>

    @if($alcance && count($alcance) > 0)
        <div class="mt-4">
            <h4 class="text-xs uppercase tracking-wider text-gray-500 mb-2">Permisos</h4>
            <div class="flex flex-wrap gap-2">
                @foreach($alcance as $permiso)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ $permiso }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mt-6 flex gap-3">
        @if($revocada)
            <button wire:click="generar"
                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Generar nueva API Key
            </button>
        @else
            <button wire:click="generar" wire:confirm="¿Generar una nueva API Key? La anterior se desactivará automáticamente."
                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Regenerar
            </button>
            <button wire:click="revocar" wire:confirm="¿Estás seguro de revocar esta API Key? Esta acción no se puede deshacer."
                class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                Revocar
            </button>
        @endif
    </div>
</div>
