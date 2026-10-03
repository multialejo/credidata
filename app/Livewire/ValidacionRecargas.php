<?php

namespace App\Livewire;

use App\Enums\EstadoRecarga;
use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Services\RecargaService;
use App\StateTransitions\Exceptions\InvalidRecargaTransitionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class ValidacionRecargas extends Component
{
    use InteractsWithFinancieroConfig;
    use WithPagination;

    public function aprobar(int $recargaId): void
    {
        abort_unless($this->puedeAcreditar(), 403);

        $recarga = Recarga::query()
            ->with('cliente.usuario')
            ->where('metodo', 'transferencia')
            ->where('estado', EstadoRecarga::Pendiente)
            ->find($recargaId);

        if (! $recarga) {
            $this->addError('aprobacion', 'Esta transferencia ya fue validada o no está disponible.');

            return;
        }

        if (! $recarga->referencia_externa || ! $recarga->comprobante_url || ! Storage::disk('local')->exists($recarga->comprobante_url) || ! $recarga->cliente?->usuario) {
            $this->addError('aprobacion', 'No se puede acreditar esta solicitud porque faltan datos del cliente o de la transferencia.');

            return;
        }

        $creditos = (int) floor((float) $recarga->monto_usd * $this->getTasaCambioUsdCreditos());
        if ($creditos < 1) {
            $this->addError('aprobacion', 'El monto registrado no alcanza para acreditar un crédito.');

            return;
        }

        try {
            DB::transaction(function () use ($recarga, $creditos): void {
                $acreditada = app(RecargaService::class)->procesar(
                    referenciaExterna: $recarga->referencia_externa,
                    clienteUid: $recarga->cliente->usuario->uid,
                    creditosObtenidos: $creditos,
                    metodoPago: 'transferencia',
                    evidenciaPath: $recarga->comprobante_url,
                    montoUsd: (float) $recarga->monto_usd,
                );

                LogActividad::create([
                    'accion' => 'recarga.acreditada_manual',
                    'actor_id' => auth()->id(),
                    'actor_sistema' => false,
                    'detalle' => [
                        'recarga_id' => $acreditada->id,
                        'creditos' => $creditos,
                        'monto_usd' => (float) $recarga->monto_usd,
                        'referencia_bancaria' => $recarga->referencia_externa,
                        'comprobante' => $recarga->comprobante_url,
                    ],
                    'ip_origen' => request()->ip(),
                ]);
            });
        } catch (InvalidRecargaTransitionException) {
            $this->addError('aprobacion', 'Esta transferencia ya fue validada o no puede acreditarse.');

            return;
        }

        session()->flash('status', 'Transferencia aprobada y créditos acreditados. Se notificó al cliente por correo.');
    }

    private function puedeAcreditar(): bool
    {
        return in_array(auth()->user()?->staff?->rol_staff, ['admin', 'support'], true);
    }

    public function render()
    {
        return view('livewire.validacion-recargas', [
            'puedeAcreditar' => $this->puedeAcreditar(),
            'recargas' => Recarga::query()
                ->with('cliente.usuario')
                ->where('metodo', 'transferencia')
                ->where('estado', EstadoRecarga::Pendiente)
                ->orderBy('created_at')
                ->paginate(10),
        ]);
    }
}
