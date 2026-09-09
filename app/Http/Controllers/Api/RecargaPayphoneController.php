<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoRecarga;
use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use App\Http\Controllers\Controller;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Services\RecargaPayphoneService;
use App\Services\RecargaService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RecargaPayphoneController extends Controller
{
    use InteractsWithFinancieroConfig;

    public function __construct(
        private RecargaPayphoneService $payphone,
        private RecargaService $recargaService,
    ) {}

    public function crearTransaccion(Request $request): JsonResponse
    {
        $minimo = $this->getRecargaMinimaUsd();

        try {
            $validated = $request->validate([
                'monto_usd' => ['required', 'numeric', 'min:'.$minimo],
            ]);
        } catch (ValidationException $e) {
            return $this->respondValidationError($e, $minimo);
        }

        $cliente = $request->user()->cliente;
        $monto = (float) $validated['monto_usd'];
        $creditos = (int) round($monto * $this->getTasaCambioUsdCreditos());
        $ctid = $this->payphone->generateClientTransactionId();

        try {
            $prepare = $this->payphone->prepare($monto, $ctid);
        } catch (ConnectionException $e) {
            return $this->respondFuenteNoDisponible($e);
        }

        $recarga = Recarga::create([
            'cliente_id' => $cliente->id,
            'metodo' => 'payphone',
            'monto_usd' => $monto,
            'creditos_obtenidos' => $creditos,
            'estado' => EstadoRecarga::Pendiente,
            'referencia_externa' => $ctid,
            'provider_payment_id' => $prepare['paymentId'],
            'provider_status' => 'PREPARED',
            'provider_currency' => config('payphone.currency'),
            'fecha' => now(),
        ]);

        return response()->json([
            'codigo' => 200,
            'exito' => true,
            'mensaje' => 'Transacción Payphone creada',
            'datos' => [
                'recarga_id' => $recarga->id,
                'client_transaction_id' => $ctid,
                'monto_usd' => $monto,
                'creditos_calculados' => $creditos,
                'pay_with_payphone' => $prepare['payWithPayPhone'],
                'pay_with_card' => $prepare['payWithCard'],
            ],
            'metadatos' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function confirmar(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'clientTransactionId' => ['required', 'string', 'max:64'],
        ]);

        $cliente = $request->user()->cliente;
        $ctid = $validated['clientTransactionId'];

        $existente = Recarga::where('referencia_externa', $ctid)->first();

        if ($existente === null) {
            return response()->json([
                'codigo' => 404,
                'exito' => false,
                'mensaje' => 'No existe una recarga con esa referencia',
                'error' => ['tipo' => 'RECARGA_NO_ENCONTRADA', 'detalle' => null],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 404);
        }

        if ($existente->cliente_id !== $cliente->id) {
            return response()->json([
                'codigo' => 403,
                'exito' => false,
                'mensaje' => 'La transacción no pertenece al cliente autenticado',
                'error' => ['tipo' => 'TRANSACCION_NO_AUTORIZADA', 'detalle' => null],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 403);
        }

        if ((string) $existente->provider_payment_id !== $id) {
            return response()->json([
                'codigo' => 409,
                'exito' => false,
                'mensaje' => 'El pago Payphone no coincide con la recarga',
                'error' => ['tipo' => 'PAGO_NO_VALIDO', 'detalle' => null],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 409);
        }

        if (in_array($existente->estado, [EstadoRecarga::Fallida, EstadoRecarga::Rechazada], true)) {
            return response()->json([
                'codigo' => 409,
                'exito' => false,
                'mensaje' => 'La recarga está en un estado terminal y no puede reintentarse',
                'error' => ['tipo' => 'TRANSICION_INVALIDA', 'detalle' => $existente->estado->value],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 409);
        }

        if ($existente->estado === EstadoRecarga::Completada) {
            return response()->json([
                'codigo' => 200,
                'exito' => true,
                'mensaje' => 'Recarga ya procesada',
                'datos' => [
                    'creditos_acreditados' => (int) $existente->creditos_obtenidos,
                    'saldo_actual' => (int) $cliente->fresh()->saldo_creditos,
                ],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ]);
        }

        try {
            $confirmacion = $this->payphone->confirm((int) $id, $ctid);
        } catch (ConnectionException $e) {
            return $this->respondFuenteNoDisponible($e);
        }

        $status = $confirmacion['transactionStatus'] ?? 'Unknown';

        $existente->update([
            'provider_transaction_id' => $confirmacion['transactionId'] ?? null,
            'provider_authorization_code' => $confirmacion['authorizationCode'] ?? null,
            'provider_status' => $status,
        ]);

        if ($status !== 'Approved') {
            if ($status === 'Canceled') {
                $existente->update(['estado' => EstadoRecarga::Fallida]);
            }

            if ($existente->estado === EstadoRecarga::Fallida) {
                LogActividad::create([
                    'accion' => 'recarga.fallida',
                    'actor_id' => $cliente->usuario->id,
                    'detalle' => [
                        'referencia_externa' => $ctid,
                        'payphone_status' => $status,
                        'payphone_message' => $confirmacion['message'] ?? null,
                    ],
                ]);
            }

            return response()->json([
                'codigo' => 200,
                'exito' => false,
                'mensaje' => $existente->estado === EstadoRecarga::Fallida ? 'Pago no completado, puede reintentar' : 'Pago pendiente de confirmación',
                'error' => [
                    'tipo' => $existente->estado === EstadoRecarga::Fallida ? 'PAGO_NO_COMPLETADO' : 'PAGO_PENDIENTE',
                    'detalle' => 'Estado Payphone: '.$status,
                ],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ]);
        }

        if (empty($confirmacion['transactionId'])) {
            return response()->json([
                'codigo' => 200,
                'exito' => false,
                'mensaje' => 'Pago pendiente de confirmación',
                'error' => ['tipo' => 'PAGO_PENDIENTE', 'detalle' => 'Payphone no devolvió un identificador de transacción'],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ]);
        }

        $existente->update(['provider_verified_at' => now()]);

        $this->recargaService->procesar(
            referenciaExterna: $ctid,
            clienteUid: $cliente->usuario->uid,
            creditosObtenidos: (int) $existente->creditos_obtenidos,
            metodoPago: 'payphone',
        );

        $saldo = (int) $cliente->fresh()->saldo_creditos;

        return response()->json([
            'codigo' => 200,
            'exito' => true,
            'mensaje' => 'Recarga acreditada',
            'datos' => [
                'creditos_acreditados' => (int) $existente->creditos_obtenidos,
                'saldo_actual' => $saldo,
            ],
            'metadatos' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    private function respondFuenteNoDisponible(ConnectionException $e): JsonResponse
    {
        return response()->json([
            'codigo' => 503,
            'exito' => false,
            'mensaje' => 'La pasarela de pago no está disponible',
            'error' => [
                'tipo' => 'FUENTE_EXTERNA_NO_DISPONIBLE',
                'detalle' => $e->getMessage(),
            ],
            'metadatos' => ['timestamp' => now()->toIso8601String()],
        ], 503);
    }

    private function respondValidationError(ValidationException $e, float $minimo): JsonResponse
    {
        return response()->json([
            'codigo' => 422,
            'exito' => false,
            'mensaje' => 'Datos de entrada inválidos',
            'error' => [
                'tipo' => 'VALIDACION',
                'detalle' => "El monto mínimo de recarga es \${$minimo} USD",
            ],
            'metadatos' => [
                'timestamp' => now()->toIso8601String(),
                'errores' => $e->errors(),
            ],
        ], 422);
    }
}
