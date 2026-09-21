<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoIntencionPaypal;
use App\Enums\EstadoRecarga;
use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\IntencionPaypal;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Services\RecargaPaypalService;
use App\Services\RecargaService;
use App\StateTransitions\IntencionPaypalTransitions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

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

        $orderId = $order['id'] ?? null;
        $approvalUrl = $this->paypal->obtenerApprovalUrl($order);

        if (! $orderId || ! $approvalUrl) {
            return $this->respondFuenteIncompleta();
        }

        $intencion = IntencionPaypal::create([
            'cliente_id' => $cliente->id,
            'order_id' => $orderId,
            'monto_usd' => $monto,
            'creditos_estimados' => $creditos,
            'moneda' => 'USD',
            'estado' => EstadoIntencionPaypal::Pendiente,
            'expira_en' => now()->addMinutes((int) config('paypal.intencion_ttl_minutes', 15)),
            'fecha' => now(),
        ]);

        return response()->json([
            'codigo' => 200,
            'exito' => true,
            'mensaje' => 'Orden PayPal creada',
            'datos' => [
                'intencion_id' => $intencion->id,
                'order_id' => $orderId,
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

        $intencion = IntencionPaypal::where('order_id', $orderId)->first();

        if ($intencion === null) {
            return response()->json([
                'codigo' => 404,
                'exito' => false,
                'mensaje' => 'No existe una recarga para la orden PayPal',
                'error' => ['tipo' => 'RECARGA_NO_ENCONTRADA', 'detalle' => null],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 404);
        }

        if ($intencion->cliente_id !== $cliente->id) {
            return response()->json([
                'codigo' => 403,
                'exito' => false,
                'mensaje' => 'La orden no pertenece al cliente autenticado',
                'error' => ['tipo' => 'ORDEN_NO_AUTORIZADA', 'detalle' => null],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 403);
        }

        $recargaCompletada = Recarga::where('referencia_externa', $orderId)
            ->where('estado', EstadoRecarga::Completada)
            ->first();

        if ($recargaCompletada) {
            return $this->respondYaProcesada($recargaCompletada, $cliente);
        }

        if (in_array($intencion->estado, [EstadoIntencionPaypal::Cancelada, EstadoIntencionPaypal::Expirada], true)) {
            return response()->json([
                'codigo' => 409,
                'exito' => false,
                'mensaje' => 'La intención está en un estado terminal y no puede reintentarse',
                'error' => ['tipo' => 'TRANSICION_INVALIDA', 'detalle' => $intencion->estado->value],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 409);
        }

        if ($intencion->estado === EstadoIntencionPaypal::Confirmada) {
            return response()->json([
                'codigo' => 200,
                'exito' => true,
                'mensaje' => 'Recarga ya procesada',
                'datos' => [
                    'creditos_acreditados' => (int) $intencion->creditos_estimados,
                    'saldo_actual' => (int) $cliente->fresh()->saldo_creditos,
                ],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ]);
        }

        try {
            $captura = $this->paypal->captureOrder($orderId, (float) $intencion->monto_usd);
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

        $providerStatus = $captura['status'] ?? 'unknown';

        if ($providerStatus !== 'COMPLETED') {
            $this->marcarCancelada($intencion, $cliente, $orderId, $providerStatus);

            return response()->json([
                'codigo' => 200,
                'exito' => false,
                'mensaje' => 'Pago no completado, puede reintentar',
                'error' => [
                    'tipo' => 'PAGO_NO_COMPLETADO',
                    'detalle' => 'Estado PayPal: '.$providerStatus,
                ],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ]);
        }

        $evidencia = $this->paypal->obtenerEvidenciaCaptura(
            $captura,
            $intencion->order_id,
            (string) $intencion->monto_usd,
        );

        if (! $evidencia) {
            $this->marcarCancelada($intencion, $cliente, $orderId, 'COMPLETED_MISMATCH');

            return response()->json([
                'codigo' => 409,
                'exito' => false,
                'mensaje' => 'El pago PayPal no coincide con la recarga',
                'error' => ['tipo' => 'PAGO_NO_VALIDO', 'detalle' => null],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 409);
        }

        try {
            $recarga = $this->recargaService->procesar(
                referenciaExterna: $orderId,
                clienteUid: $cliente->usuario->uid,
                creditosObtenidos: (int) $intencion->creditos_estimados,
                metodoPago: 'paypal',
                montoUsd: (float) $intencion->monto_usd,
            );
        } catch (Throwable $e) {
            Log::error('API procesar failed (PayPal)', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'codigo' => 503,
                'exito' => false,
                'mensaje' => 'Pago pendiente de confirmación, reintentá luego',
                'error' => ['tipo' => 'PAGO_PENDIENTE', 'detalle' => 'No se pudo acreditar el saldo'],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 503);
        }

        $recarga->forceFill($evidencia)->save();

        IntencionPaypalTransitions::assert($intencion->estado, EstadoIntencionPaypal::Confirmada);
        $intencion->update(['estado' => EstadoIntencionPaypal::Confirmada]);

        $saldo = (int) $cliente->fresh()->saldo_creditos;

        return response()->json([
            'codigo' => 200,
            'exito' => true,
            'mensaje' => 'Recarga acreditada',
            'datos' => [
                'creditos_acreditados' => (int) $recarga->creditos_obtenidos,
                'saldo_actual' => $saldo,
            ],
            'metadatos' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    private function respondYaProcesada(Recarga $recarga, Cliente $cliente): JsonResponse
    {
        return response()->json([
            'codigo' => 200,
            'exito' => true,
            'mensaje' => 'Recarga ya procesada',
            'datos' => [
                'creditos_acreditados' => (int) $recarga->creditos_obtenidos,
                'saldo_actual' => (int) $cliente->fresh()->saldo_creditos,
            ],
            'metadatos' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    private function marcarCancelada(IntencionPaypal $intencion, Cliente $cliente, string $orderId, string $status): void
    {
        IntencionPaypalTransitions::assert($intencion->estado, EstadoIntencionPaypal::Cancelada);
        $intencion->update(['estado' => EstadoIntencionPaypal::Cancelada]);

        LogActividad::create([
            'accion' => 'recarga.fallida',
            'actor_id' => $cliente->usuario->id,
            'detalle' => [
                'referencia_externa' => $orderId,
                'paypal_status' => $status,
            ],
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

    private function respondFuenteIncompleta(): JsonResponse
    {
        return response()->json([
            'codigo' => 502,
            'exito' => false,
            'mensaje' => 'La pasarela de pago devolvió una respuesta incompleta',
            'error' => ['tipo' => 'FUENTE_EXTERNA_INCOMPLETA', 'detalle' => null],
            'metadatos' => ['timestamp' => now()->toIso8601String()],
        ], 502);
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
