<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoIntencionPayphone;
use App\Enums\EstadoRecarga;
use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\IntencionPayphone;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Services\RecargaPayphoneService;
use App\Services\RecargaService;
use App\StateTransitions\IntencionPayphoneTransitions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

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
        $maximo = $this->getRecargaMaximaPayphoneUsd();

        try {
            $validated = $request->validate([
                'monto_usd' => ['required', 'numeric', 'min:'.$minimo, 'max:'.$maximo],
            ]);
        } catch (ValidationException $e) {
            return $this->respondValidationError($e, $minimo, $maximo);
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

        $intencion = IntencionPayphone::create([
            'cliente_id' => $cliente->id,
            'ctid' => $ctid,
            'payment_id' => $prepare['paymentId'],
            'monto_usd' => $monto,
            'creditos_estimados' => $creditos,
            'moneda' => config('payphone.currency'),
            'estado' => EstadoIntencionPayphone::Pendiente,
            'expira_en' => now()->addMinutes((int) config('payphone.intencion_ttl_minutes', 15)),
            'fecha' => now(),
        ]);

        return response()->json([
            'codigo' => 200,
            'exito' => true,
            'mensaje' => 'Transacción Payphone creada',
            'datos' => [
                'intencion_id' => $intencion->id,
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

        $intencion = IntencionPayphone::where('ctid', $ctid)->first();

        if ($intencion === null) {
            return response()->json([
                'codigo' => 404,
                'exito' => false,
                'mensaje' => 'No existe una recarga con esa referencia',
                'error' => ['tipo' => 'RECARGA_NO_ENCONTRADA', 'detalle' => null],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 404);
        }

        if ($intencion->cliente_id !== $cliente->id) {
            return response()->json([
                'codigo' => 403,
                'exito' => false,
                'mensaje' => 'La transacción no pertenece al cliente autenticado',
                'error' => ['tipo' => 'TRANSACCION_NO_AUTORIZADA', 'detalle' => null],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 403);
        }

        if ((string) $intencion->payment_id !== $id) {
            return response()->json([
                'codigo' => 409,
                'exito' => false,
                'mensaje' => 'El pago Payphone no coincide con la intención',
                'error' => ['tipo' => 'PAGO_NO_VALIDO', 'detalle' => null],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 409);
        }

        $recargaCompletada = Recarga::where('referencia_externa', $ctid)
            ->where('estado', EstadoRecarga::Completada)
            ->first();

        if ($recargaCompletada) {
            return $this->respondYaProcesada($recargaCompletada, $cliente);
        }

        if (in_array($intencion->estado, [EstadoIntencionPayphone::Cancelada, EstadoIntencionPayphone::Expirada], true)) {
            return response()->json([
                'codigo' => 409,
                'exito' => false,
                'mensaje' => 'La intención está en un estado terminal y no puede reintentarse',
                'error' => ['tipo' => 'TRANSICION_INVALIDA', 'detalle' => $intencion->estado->value],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 409);
        }

        if ($intencion->estado === EstadoIntencionPayphone::Confirmada) {
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
            $confirmacion = $this->payphone->confirm((int) $id, $ctid, (float) $intencion->monto_usd);
        } catch (ConnectionException $e) {
            Log::error('API confirm Payphone connection failed', [
                'clientTransactionId' => $ctid,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'codigo' => 503,
                'exito' => false,
                'mensaje' => 'Pago pendiente de confirmación, reintentá luego',
                'error' => [
                    'tipo' => 'PAGO_PENDIENTE',
                    'detalle' => 'La pasarela de pago no está disponible',
                ],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 503);
        }

        $status = $confirmacion['transactionStatus'] ?? 'Unknown';

        if ($status !== 'Approved') {
            $this->marcarCancelada($intencion, $cliente, $ctid, $status, $confirmacion);

            return response()->json([
                'codigo' => 200,
                'exito' => false,
                'mensaje' => 'Pago no completado, puede reintentar',
                'error' => [
                    'tipo' => 'PAGO_NO_COMPLETADO',
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

        $montoPagado = round((float) ($confirmacion['amountPaidUsd'] ?? 0), 2);
        $montoEsperado = round((float) $intencion->monto_usd, 2);

        if ($montoPagado !== $montoEsperado) {
            $this->marcarCancelada($intencion, $cliente, $ctid, $status, $confirmacion);

            return response()->json([
                'codigo' => 409,
                'exito' => false,
                'mensaje' => 'El monto pagado no coincide con el monto de la intención',
                'error' => [
                    'tipo' => 'MONTO_NO_COINCIDE',
                    'detalle' => ['pagado_usd' => $montoPagado, 'esperado_usd' => $montoEsperado],
                ],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 409);
        }

        try {
            $recarga = $this->recargaService->procesar(
                referenciaExterna: $ctid,
                clienteUid: $cliente->usuario->uid,
                creditosObtenidos: (int) $intencion->creditos_estimados,
                metodoPago: 'payphone',
                montoUsd: $montoEsperado,
            );
        } catch (Throwable $e) {
            Log::error('API procesar failed (Payphone)', [
                'clientTransactionId' => $ctid,
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

        $recarga->forceFill([
            'provider_payment_id' => (string) $id,
            'provider_transaction_id' => $confirmacion['transactionId'],
            'provider_authorization_code' => $confirmacion['authorizationCode'] ?? null,
            'provider_status' => $status,
            'provider_currency' => config('payphone.currency'),
            'provider_verified_at' => now(),
        ])->save();

        IntencionPayphoneTransitions::assert($intencion->estado, EstadoIntencionPayphone::Confirmada);
        $intencion->update(['estado' => EstadoIntencionPayphone::Confirmada]);

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

    private function marcarCancelada(IntencionPayphone $intencion, Cliente $cliente, string $ctid, string $status, array $confirmacion): void
    {
        IntencionPayphoneTransitions::assert($intencion->estado, EstadoIntencionPayphone::Cancelada);
        $intencion->update(['estado' => EstadoIntencionPayphone::Cancelada]);

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

    private function respondValidationError(ValidationException $e, float $minimo, float $maximo): JsonResponse
    {
        return response()->json([
            'codigo' => 422,
            'exito' => false,
            'mensaje' => 'Datos de entrada inválidos',
            'error' => [
                'tipo' => 'VALIDACION',
                'detalle' => "El monto de recarga debe ser entre \${$minimo} y \${$maximo} USD",
            ],
            'metadatos' => [
                'timestamp' => now()->toIso8601String(),
                'errores' => $e->errors(),
            ],
        ], 422);
    }
}
