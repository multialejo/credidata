<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoRecarga;
use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use App\Http\Controllers\Controller;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Services\RecargaPaypalService;
use App\Services\RecargaService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RecargaPaypalController extends Controller
{
    use InteractsWithFinancieroConfig;

    public function __construct(
        private RecargaPaypalService $paypal,
        private RecargaService $recargaService,
    ) {}

    public function crearOrden(Request $request): JsonResponse
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
        $creditos = (int) floor($monto * $this->getTasaCambioUsdCreditos());

        try {
            $order = $this->paypal->createOrder($monto);
        } catch (ConnectionException $e) {
            return $this->respondFuenteNoDisponible($e);
        }

        $approvalUrl = $this->paypal->obtenerApprovalUrl($order);

        Recarga::create([
            'cliente_id' => $cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => $monto,
            'creditos_obtenidos' => $creditos,
            'estado' => EstadoRecarga::Pendiente,
            'referencia_externa' => $order['id'],
            'fecha' => now(),
        ]);

        return response()->json([
            'codigo' => 200,
            'exito' => true,
            'mensaje' => 'Orden PayPal creada',
            'datos' => [
                'order_id' => $order['id'],
                'approval_url' => $approvalUrl,
                'monto_usd' => $monto,
                'creditos_calculados' => $creditos,
            ],
            'metadatos' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function capturar(Request $request, string $orderId): JsonResponse
    {
        $cliente = $request->user()->cliente;

        $existente = Recarga::where('referencia_externa', $orderId)->first();
        if (! $existente) {
            return response()->json([
                'codigo' => 404,
                'exito' => false,
                'mensaje' => 'No existe una recarga para la orden PayPal',
                'error' => ['tipo' => 'RECARGA_NO_ENCONTRADA', 'detalle' => null],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 404);
        }

        if ($existente->cliente_id !== $cliente->id) {
            return response()->json([
                'codigo' => 403,
                'exito' => false,
                'mensaje' => 'La orden no pertenece al cliente autenticado',
                'error' => ['tipo' => 'ORDEN_NO_AUTORIZADA', 'detalle' => null],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 403);
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

        if (in_array($existente->estado, [EstadoRecarga::Fallida, EstadoRecarga::Rechazada], true)) {
            return response()->json([
                'codigo' => 409,
                'exito' => false,
                'mensaje' => 'La recarga está en un estado terminal y no puede reintentarse',
                'error' => ['tipo' => 'TRANSICION_INVALIDA', 'detalle' => $existente->estado->value],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 409);
        }

        try {
            $captura = $this->paypal->captureOrder($orderId);
        } catch (ConnectionException $e) {
            return $this->respondFuenteNoDisponible($e);
        }

        if (($captura['status'] ?? null) === 'NOT_FOUND') {
            return response()->json([
                'codigo' => 404,
                'exito' => false,
                'mensaje' => 'Orden PayPal no encontrada',
                'error' => ['tipo' => 'ORDEN_NO_ENCONTRADA', 'detalle' => "order_id: {$orderId}"],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 404);
        }

        if (($captura['status'] ?? null) !== 'COMPLETED') {
            $providerStatus = $captura['status'] ?? 'unknown';
            if (in_array($providerStatus, ['DECLINED', 'VOIDED', 'CANCELED', 'DENIED', 'EXPIRED'], true)) {
                $existente->update(['estado' => EstadoRecarga::Fallida]);
            } else {
                $existente->update(['provider_status' => $providerStatus]);
            }

            if ($existente->estado === EstadoRecarga::Fallida) {
                LogActividad::create([
                    'accion' => 'recarga.fallida',
                    'actor_id' => $cliente->usuario->id,
                    'detalle' => [
                        'referencia_externa' => $orderId,
                        'paypal_status' => $providerStatus,
                    ],
                ]);
            }

            return response()->json([
                'codigo' => 200,
                'exito' => false,
                'mensaje' => $existente->estado === EstadoRecarga::Fallida ? 'Pago no completado, puede reintentar' : 'Pago pendiente de confirmación',
                'error' => [
                    'tipo' => $existente->estado === EstadoRecarga::Fallida ? 'PAGO_NO_COMPLETADO' : 'PAGO_PENDIENTE',
                    'detalle' => 'Estado PayPal: '.$providerStatus,
                ],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ]);
        }

        $evidencia = $this->paypal->obtenerEvidenciaCaptura(
            $captura,
            $existente->referencia_externa,
            $existente->monto_usd,
        );

        if (! $evidencia) {
            $existente->update([
                'estado' => EstadoRecarga::Fallida,
                'provider_status' => 'COMPLETED_MISMATCH',
            ]);
            LogActividad::create([
                'accion' => 'recarga.fallida',
                'actor_id' => $cliente->usuario->id,
                'detalle' => ['referencia_externa' => $orderId, 'paypal_status' => 'COMPLETED_MISMATCH'],
            ]);

            return response()->json([
                'codigo' => 409,
                'exito' => false,
                'mensaje' => 'El pago PayPal no coincide con la recarga',
                'error' => ['tipo' => 'PAGO_NO_VALIDO', 'detalle' => null],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 409);
        }

        $existente->update($evidencia);

        $creditos = (int) $existente->creditos_obtenidos;

        $this->recargaService->procesar(
            referenciaExterna: $orderId,
            clienteUid: $cliente->usuario->uid,
            creditosObtenidos: $creditos,
            metodoPago: 'paypal',
        );

        $saldo = (int) $cliente->fresh()->saldo_creditos;

        return response()->json([
            'codigo' => 200,
            'exito' => true,
            'mensaje' => 'Recarga acreditada',
            'datos' => [
                'creditos_acreditados' => $creditos,
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
