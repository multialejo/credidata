<?php

namespace App\Livewire;

use App\Enums\EstadoRecarga;
use App\Models\Recarga;
use App\Services\RecargaService;
use App\StateTransitions\RecargaTransitions;
use Livewire\Component;

class ValidacionRecargas extends Component
{
    public $creditos = '';

    public $motivo = '';

    public $recargaSeleccionada;

    public $mostrarFormulario = false;

    public $esAdmin = false;

    public function mount(): void
    {
        $this->esAdmin = auth()->user()?->staff?->rol_staff === 'admin';
    }

    public function seleccionarRecarga(Recarga $recarga): void
    {
        $this->recargaSeleccionada = $recarga;
        $this->mostrarFormulario = true;
        $this->creditos = '';
        $this->motivo = '';
    }

    public function cerrarFormulario(): void
    {
        $this->mostrarFormulario = false;
        $this->recargaSeleccionada = null;
    }

    public function acreditar(): void
    {
        if (! $this->esAdmin) {
            return;
        }

        $this->validate([
            'creditos' => 'required|integer|min:1',
            'motivo' => 'required|string|min:1',
        ]);

        $recarga = Recarga::lockForUpdate()->find($this->recargaSeleccionada->id);

        RecargaTransitions::assert($recarga->estado, EstadoRecarga::Completada);

        $service = app(RecargaService::class);
        $service->procesar(
            referenciaExterna: $recarga->referencia_externa,
            clienteUid: $recarga->cliente->usuario->uid,
            creditos: (int) $this->creditos,
            metodo: $recarga->metodo,
            evidenciaPath: $recarga->comprobante_url
        );

        LogActividad::create([
            'actor_id' => auth()->id(),
            'accion' => 'recarga.acreditada_manual',
            'detalle' => json_encode([
                'recarga_id' => $recarga->id,
                'creditos' => $this->creditos,
                'motivo' => $this->motivo,
            ]),
        ]);

        $this->mostrarFormulario = false;
        $this->recargaSeleccionada = null;
    }

    public function rechazar(): void
    {
        $this->validate(['motivo' => 'required|string|min:1']);

        $recarga = Recarga::lockForUpdate()->find($this->recargaSeleccionada->id);

        RecargaTransitions::assert($recarga->estado, EstadoRecarga::Rechazada);

        $recarga->update(['estado' => EstadoRecarga::Rechazada]);

        LogActividad::create([
            'actor_id' => auth()->id(),
            'accion' => 'recarga.rechazada_manual',
            'detalle' => json_encode([
                'recarga_id' => $recarga->id,
                'motivo' => $this->motivo,
            ]),
        ]);

        $this->mostrarFormulario = false;
        $this->recargaSeleccionada = null;
    }

    public function render()
    {
        $pendientes = Recarga::where('estado', EstadoRecarga::Pendiente)
            ->with('cliente.usuario')
            ->latest('fecha')
            ->paginate(15);

        return view('livewire.validacion-recargas', ['pendientes' => $pendientes]);
    }
}
