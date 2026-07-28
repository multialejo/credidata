<?php

namespace App\Http\Controllers\Api;

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
            'estado' => 'pendiente',
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
        if ($existente && $existente->estado === 'completada') {
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

        if ($existente && $existente->cliente_id !== $cliente->id) {
            return response()->json([
                'codigo' => 403,
                'exito' => false,
                'mensaje' => 'La orden no pertenece al cliente autenticado',
                'error' => ['tipo' => 'ORDEN_NO_AUTORIZADA', 'detalle' => null],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 403);
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
            if ($existente) {
                $existente->update(['estado' => 'fallida']);
            }
            LogActividad::create([
                'accion' => 'recarga.fallida',
                'actor_id' => $cliente->usuario->id,
                'detalle' => [
                    'referencia_externa' => $orderId,
                    'paypal_status' => $captura['status'] ?? 'unknown',
                ],
            ]);

            return response()->json([
                'codigo' => 200,
                'exito' => false,
                'mensaje' => 'Pago no completado, puede reintentar',
                'error' => [
                    'tipo' => 'PAGO_NO_COMPLETADO',
                    'detalle' => 'Estado PayPal: '.($captura['status'] ?? 'unknown'),
                ],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ]);
        }

        $creditos = (int) ($existente?->creditos_obtenidos ?? 0);

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
