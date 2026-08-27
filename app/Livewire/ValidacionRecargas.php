<?php

namespace App\Livewire;

use App\Enums\EstadoRecarga;
use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use App\Jobs\SendRecargaRechazadaEmail;
use App\Models\Cliente;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Services\RecargaService;
use App\StateTransitions\Exceptions\InvalidRecargaTransitionException;
use App\StateTransitions\RecargaTransitions;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ValidacionRecargas extends Component
{
    use InteractsWithFinancieroConfig;
    use WithFileUploads, WithPagination;

    public string $clienteEmail = '';

    public string $montoUsd = '';

    public string $referenciaBancaria = '';

    public string $motivo = '';

    public $comprobante;

    public ?int $rechazandoId = null;

    public string $motivoRechazo = '';

    public function getCreditosCalculadosProperty(): int
    {
        return $this->montoUsd === ''
            ? 0
            : (int) floor((float) $this->montoUsd * $this->getTasaCambioUsdCreditos());
    }

    public function acreditar(): void
    {
        abort_unless($this->esAdmin(), 403);

        $this->validate([
            'clienteEmail' => ['required', 'email', 'max:255'],
            'montoUsd' => ['required', 'numeric', 'min:0.01'],
            'referenciaBancaria' => ['required', 'string', 'max:100'],
            'motivo' => ['required', 'string', 'min:10', 'max:500'],
            'comprobante' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        $cliente = Cliente::whereHas('usuario', fn ($query) => $query->where('email', $this->clienteEmail))->first();
        if (! $cliente) {
            $this->addError('clienteEmail', 'No existe un cliente con ese email.');

            return;
        }

        if (Recarga::where('referencia_externa', $this->referenciaBancaria)->exists()) {
            $this->addError('referenciaBancaria', 'La referencia bancaria ya fue registrada.');

            return;
        }

        $path = $this->comprobante->store('recargas/comprobantes');

        try {
            $creditos = (int) floor((float) $this->montoUsd * $this->getTasaCambioUsdCreditos());
            if ($creditos < 1) {
                $this->addError('montoUsd', 'El monto no alcanza para acreditar un crédito.');

                return;
            }

            DB::transaction(function () use ($cliente, $path, $creditos) {
                $recarga = app(RecargaService::class)->procesar(
                    referenciaExterna: $this->referenciaBancaria,
                    clienteUid: $cliente->usuario->uid,
                    creditosObtenidos: $creditos,
                    metodoPago: 'transferencia',
                    evidenciaPath: $path,
                    montoUsd: (float) $this->montoUsd,
                );

                LogActividad::create([
                    'accion' => 'recarga.acreditada_manual',
                    'actor_id' => auth()->id(),
                    'actor_sistema' => false,
                    'detalle' => [
                        'recarga_id' => $recarga->id,
                        'creditos' => $creditos,
                        'monto_usd' => (float) $this->montoUsd,
                        'referencia_bancaria' => $this->referenciaBancaria,
                        'motivo' => $this->motivo,
                        'comprobante' => $path,
                    ],
                    'ip_origen' => request()->ip(),
                ]);
            });
        } catch (InvalidRecargaTransitionException) {
            $this->addError('referenciaBancaria', 'La referencia no puede acreditarse en su estado actual.');

            return;
        }

        $this->reset(['clienteEmail', 'montoUsd', 'referenciaBancaria', 'motivo', 'comprobante']);
        session()->flash('status', 'Transferencia acreditada correctamente.');
        $this->resetPage();
    }

    public function toggleRechazar(int $id): void
    {
        $this->rechazandoId = $this->rechazandoId === $id ? null : $id;
        $this->motivoRechazo = '';
        $this->resetValidation();
    }

    public function rechazar(int $id): void
    {
        $this->validate(['motivoRechazo' => ['required', 'string', 'min:10', 'max:500']]);

        $candidate = Recarga::findOrFail($id);
        if (! in_array($candidate->metodo, ['paypal', 'payphone'], true)) {
            $this->addError('rechazo', 'Solo se pueden rechazar pagos de pasarela.');

            return;
        }

        try {
            $recarga = DB::transaction(function () use ($id) {
                $recarga = Recarga::lockForUpdate()->findOrFail($id);
                RecargaTransitions::assert($recarga->estado, EstadoRecarga::Rechazada);
                $recarga->update([
                    'estado' => EstadoRecarga::Rechazada,
                    'motivo_rechazo' => $this->motivoRechazo,
                    'rechazada_por' => auth()->id(),
                    'rechazada_at' => now(),
                ]);
                LogActividad::create([
                    'accion' => 'recarga.rechazada',
                    'actor_id' => auth()->id(),
                    'detalle' => ['recarga_id' => $id, 'motivo' => $this->motivoRechazo],
                ]);

                return $recarga->fresh(['cliente.usuario']);
            });
        } catch (InvalidRecargaTransitionException) {
            $this->addError('rechazo', 'La recarga ya no puede rechazarse en su estado actual.');

            return;
        }

        DB::afterCommit(fn () => dispatch(new SendRecargaRechazadaEmail($recarga)));
        $this->toggleRechazar($id);
        session()->flash('status', 'Recarga rechazada correctamente.');
        $this->resetPage();
    }

    private function esAdmin(): bool
    {
        return auth()->user()?->staff?->rol_staff === 'admin';
    }

    public function render()
    {
        return view('livewire.validacion-recargas', [
            'recargas' => Recarga::with('cliente.usuario')
                ->where('estado', EstadoRecarga::Pendiente)
                ->whereIn('metodo', ['paypal', 'payphone'])
                ->latest('fecha')
                ->paginate(15),
            'esAdmin' => $this->esAdmin(),
        ]);
    }
}
