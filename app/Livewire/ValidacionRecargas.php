<?php

namespace App\Livewire;

use App\Enums\EstadoRecarga;
use App\Jobs\SendRecargaRechazadaEmail;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Services\RecargaService;
use App\StateTransitions\Exceptions\InvalidRecargaTransitionException;
use App\StateTransitions\RecargaTransitions;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ValidacionRecargas extends Component
{
    use WithPagination;

    /** Recarga cuya fila está en modo "Acreditar" (inline form). */
    public ?int $acreditandoId = null;

    /** Créditos a acreditar (form inline de acreditación). */
    public int $creditos = 1;

    /** Motivo de la acreditación (contexto auditoría, REQ §9.3). */
    public string $motivo = '';

    /** Recarga cuya fila está en modo "Rechazar" (inline form). */
    public ?int $rechazandoId = null;

    /** Motivo del rechazo (flujo idéntico al endpoint del Sprint 5). */
    public string $motivoRechazo = '';

    public function toggleAcreditar(int $recargaId): void
    {
        $this->acreditandoId = $this->acreditandoId === $recargaId ? null : $recargaId;
        $this->rechazandoId = null;
        $this->reset(['creditos', 'motivo']);
        $this->resetValidation();
    }

    public function toggleRechazar(int $recargaId): void
    {
        $this->rechazandoId = $this->rechazandoId === $recargaId ? null : $recargaId;
        $this->acreditandoId = null;
        $this->reset(['motivoRechazo']);
        $this->resetValidation();
    }

    public function acreditar(int $recargaId): void
    {
        if (! $this->esAdmin()) {
            $this->addError('acreditacion', 'Solo el rol admin puede acreditar créditos.');

            return;
        }

        $this->validate([
            'creditos' => ['required', 'integer', 'min:1'],
            'motivo' => ['required', 'string', 'max:500'],
        ]);

        $recarga = Recarga::findOrFail($recargaId);

        try {
            DB::transaction(function () use ($recarga) {
                $locked = Recarga::lockForUpdate()->findOrFail($recarga->id);

                RecargaTransitions::assert($locked->estado, EstadoRecarga::Completada);

                app(RecargaService::class)->procesar(
                    referenciaExterna: $locked->referencia_externa,
                    clienteUid: $locked->cliente->usuario->uid,
                    creditosObtenidos: $this->creditos,
                    metodoPago: 'transferencia',
                    evidenciaPath: $locked->comprobante_url,
                );

                LogActividad::create([
                    'accion' => 'recarga.acreditada_manual',
                    'actor_id' => auth()->id(),
                    'actor_sistema' => false,
                    'detalle' => [
                        'recarga_id' => $locked->id,
                        'creditos' => $this->creditos,
                        'motivo' => $this->motivo,
                    ],
                    'ip_origen' => request()->ip(),
                ]);
            });
        } catch (InvalidRecargaTransitionException) {
            $this->addError('acreditacion', 'La recarga ya no puede acreditarse en su estado actual.');

            return;
        }

        $this->toggleAcreditar($recargaId);
        $this->resetPage();
        session()->flash('status', 'Créditos acreditados correctamente.');
    }

    public function rechazar(int $recargaId): void
    {
        $this->validate([
            'motivoRechazo' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $recarga = Recarga::findOrFail($recargaId);

        try {
            DB::transaction(function () use ($recarga) {
                $locked = Recarga::lockForUpdate()->findOrFail($recarga->id);

                RecargaTransitions::assert($locked->estado, EstadoRecarga::Rechazada);

                $locked->update([
                    'estado' => EstadoRecarga::Rechazada,
                    'motivo_rechazo' => $this->motivoRechazo,
                    'rechazada_por' => auth()->id(),
                    'rechazada_at' => now(),
                ]);

                LogActividad::create([
                    'accion' => 'recarga.rechazada',
                    'actor_id' => auth()->id(),
                    'detalle' => [
                        'recarga_id' => $locked->id,
                        'motivo' => $this->motivoRechazo,
                        'referencia_externa' => $locked->referencia_externa,
                    ],
                ]);
            });
        } catch (InvalidRecargaTransitionException) {
            $this->addError('rechazo', 'La recarga ya no puede rechazarse en su estado actual.');

            return;
        }

        $recarga->refresh();

        DB::afterCommit(function () use ($recarga) {
            dispatch(new SendRecargaRechazadaEmail($recarga));
        });

        $this->toggleRechazar($recargaId);
        $this->resetPage();
        session()->flash('status', 'Recarga rechazada correctamente.');
    }

    private function esAdmin(): bool
    {
        return auth()->user()?->staff?->rol_staff === 'admin';
    }

    public function render()
    {
        $recargas = Recarga::query()
            ->with(['cliente.usuario'])
            ->where('estado', EstadoRecarga::Pendiente)
            ->latest('fecha')
            ->paginate(10);

        return view('livewire.validacion-recargas', [
            'recargas' => $recargas,
            'esAdmin' => $this->esAdmin(),
        ]);
    }
}