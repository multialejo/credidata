<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoRecarga;
use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AcreditarRecargaRequest;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Models\Usuario;
use App\Services\RecargaService;
use App\StateTransitions\Exceptions\InvalidRecargaTransitionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminRecargaController extends Controller
{
    use InteractsWithFinancieroConfig;

    public function acreditar(AcreditarRecargaRequest $request): JsonResponse
    {
        $staff = $request->user();
        $monto = (float) $request->validated('monto_usd');
        $creditos = (int) floor($monto * $this->getTasaCambioUsdCreditos());
        if ($creditos < 1) {
            throw ValidationException::withMessages([
                'monto_usd' => 'El monto no alcanza para acreditar un crédito.',
            ]);
        }
        $motivo = $request->validated('motivo');
        $referencia = $request->validated('referencia_bancaria');
        $usuario = Usuario::where('email', $request->validated('cliente_email'))
            ->whereHas('cliente')
            ->first();

        if (! $usuario) {
            throw ValidationException::withMessages([
                'cliente_email' => 'No existe un cliente con ese email.',
            ]);
        }

        $existente = Recarga::where('referencia_externa', $referencia)->first();
        if ($existente) {
            if ($existente->cliente_id !== $usuario->cliente->id) {
                return $this->error('La referencia bancaria ya pertenece a otro cliente.', 'REFERENCIA_DUPLICADA', 409);
            }

            if ($existente->estado === EstadoRecarga::Completada) {
                return $this->acreditacionResponse($existente->fresh(['cliente.usuario']));
            }

            return $this->error('La referencia bancaria ya fue registrada.', 'REFERENCIA_DUPLICADA', 409);
        }

        $evidenciaPath = $request->file('comprobante')->store('recargas/comprobantes');

        try {
            $recarga = DB::transaction(function () use ($usuario, $referencia, $monto, $creditos, $evidenciaPath, $staff, $motivo, $request) {
                $recarga = app(RecargaService::class)->procesar(
                    referenciaExterna: $referencia,
                    clienteUid: $usuario->uid,
                    creditosObtenidos: $creditos,
                    metodoPago: 'transferencia',
                    evidenciaPath: $evidenciaPath,
                    montoUsd: $monto,
                );

                LogActividad::create([
                    'accion' => 'recarga.acreditada_manual',
                    'actor_id' => $staff->id,
                    'actor_sistema' => false,
                    'detalle' => [
                        'recarga_id' => $recarga->id,
                        'creditos' => $creditos,
                        'monto_usd' => $monto,
                        'referencia_bancaria' => $referencia,
                        'motivo' => $motivo,
                        'comprobante' => $recarga->comprobante_url,
                    ],
                    'ip_origen' => $request->ip(),
                ]);

                return $recarga;
            });
        } catch (InvalidRecargaTransitionException $e) {
            return $this->error('La recarga no puede acreditarse en su estado actual.', 'TRANSICION_INVALIDA', 409, $e->getMessage());
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
