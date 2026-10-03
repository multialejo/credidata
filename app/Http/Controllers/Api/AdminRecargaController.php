<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoRecarga;
use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AcreditarRecargaRequest;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Services\RecargaService;
use App\StateTransitions\Exceptions\InvalidRecargaTransitionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminRecargaController extends Controller
{
    use InteractsWithFinancieroConfig;

    public function acreditar(AcreditarRecargaRequest $request): JsonResponse
    {
        $staff = $request->user();
        $recarga = Recarga::query()
            ->with('cliente.usuario')
            ->where('metodo', 'transferencia')
            ->where('estado', EstadoRecarga::Pendiente)
            ->find($request->validated('recarga_id'));

        if (! $recarga || ! $recarga->referencia_externa || ! $recarga->comprobante_url || ! Storage::disk('local')->exists($recarga->comprobante_url) || ! $recarga->cliente?->usuario) {
            return $this->error('La solicitud no existe, no está pendiente o no tiene comprobante.', 'SOLICITUD_NO_DISPONIBLE', 409);
        }

        $creditos = (int) floor((float) $recarga->monto_usd * $this->getTasaCambioUsdCreditos());
        if ($creditos < 1) {
            return $this->error('El monto no alcanza para acreditar un crédito.', 'MONTO_INVALIDO', 422);
        }

        try {
            $recarga = DB::transaction(function () use ($recarga, $creditos, $staff, $request): Recarga {
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
                    'actor_id' => $staff->id,
                    'actor_sistema' => false,
                    'detalle' => [
                        'recarga_id' => $acreditada->id,
                        'creditos' => $creditos,
                        'monto_usd' => (float) $recarga->monto_usd,
                        'referencia_bancaria' => $recarga->referencia_externa,
                        'comprobante' => $recarga->comprobante_url,
                    ],
                    'ip_origen' => $request->ip(),
                ]);

                return $acreditada;
            });
        } catch (InvalidRecargaTransitionException $exception) {
            return $this->error('La recarga ya fue validada o no puede acreditarse.', 'TRANSICION_INVALIDA', 409, $exception->getMessage());
        }

        return $this->acreditacionResponse($recarga->fresh(['cliente.usuario']));
    }

    private function acreditacionResponse(Recarga $recarga): JsonResponse
    {
        return response()->json([
            'codigo' => 200,
            'exito' => true,
            'mensaje' => 'Recarga acreditada',
            'datos' => ['recarga' => [
                'id' => $recarga->id,
                'estado' => $recarga->estado->value,
                'creditos_obtenidos' => $recarga->creditos_obtenidos,
                'cliente' => [
                    'nombre' => $recarga->cliente?->usuario?->nombre,
                    'email' => $recarga->cliente?->usuario?->email,
                ],
            ]],
            'metadatos' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    private function error(string $mensaje, string $tipo, int $status, ?string $detalle = null): JsonResponse
    {
        return response()->json([
            'codigo' => $status,
            'exito' => false,
            'mensaje' => $mensaje,
            'error' => ['tipo' => $tipo, 'detalle' => $detalle],
            'metadatos' => ['timestamp' => now()->toIso8601String()],
        ], $status);
    }
}
