<?php

namespace App\Http\Controllers;

use App\Enums\EstadoIntencionPaypal;
use App\Enums\EstadoRecarga;
use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use App\Models\IntencionPaypal;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Services\RecargaPaypalService;
use App\Services\RecargaService;
use App\StateTransitions\IntencionPaypalTransitions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class RecargaPaypalReturnController extends Controller
{
    use InteractsWithFinancieroConfig;

    public function showReturn(
        Request $request,
        RecargaPaypalService $service,
        RecargaService $recargaService,
    ): View {
        $token = $request->query('token');

        $intencion = ($token && is_string($token))
            ? IntencionPaypal::where('order_id', $token)->first()
            : null;

        // Sin intención, o usuario autenticado que no es el dueño de la orden.
        // REQ-04: misma vista byte-identical que token inexistente (no info leak).
        if (! $intencion) {
            return view('recargas.paypal.return', [
                'status' => 'not_found',
                'intencion' => null,
            ]);
        }

        $user = $request->user();
        if ($user && $intencion->cliente_id !== $user->cliente?->id) {
            return view('recargas.paypal.return', [
                'status' => 'not_found',
                'intencion' => null,
            ]);
        }

        // Idempotencia: la orden ya fue acreditada, no re-capturar.
        $recargaCompletada = Recarga::where('referencia_externa', $token)
            ->where('estado', EstadoRecarga::Completada)
            ->first();

        if ($recargaCompletada) {
            return view('recargas.paypal.return', [
                'status' => 'completada',
                'intencion' => $intencion,
                'recarga' => $recargaCompletada,
            ]);
        }

        // Intención ya confirmada (recarga confirmada ausente por un caso raro).
        if ($intencion->estado === EstadoIntencionPaypal::Confirmada) {
            return view('recargas.paypal.return', [
                'status' => 'completada',
                'intencion' => $intencion,
                'recarga' => null,
            ]);
        }

        // Cancelada o expirada: terminal, no reintentar.
        if (in_array($intencion->estado, [EstadoIntencionPaypal::Cancelada, EstadoIntencionPaypal::Expirada], true)) {
            return view('recargas.paypal.return', [
                'status' => 'fallida',
                'intencion' => $intencion,
            ]);
        }

        // Pendiente + guest, mostrar status solamente.
        if (! $user) {
            return view('recargas.paypal.return', [
                'status' => 'pendiente',
                'intencion' => $intencion,
            ]);
        }

        // Pendiente + autenticado + dueño, capturar + validar monto + acreditar.
        try {
            $capture = $service->captureOrder($token, (float) $intencion->monto_usd);
        } catch (ConnectionException $e) {
            Log::error('Return capture failed (PayPal connection)', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return view('recargas.paypal.return', [
                'status' => 'pendiente',
                'intencion' => $intencion,
            ]);
        }

        $providerStatus = $capture['status'] ?? 'unknown';

        if ($providerStatus !== 'COMPLETED') {
            $this->cancela($intencion, $user->cliente->usuario->id, $token, $providerStatus);

            return view('recargas.paypal.return', [
                'status' => 'fallida',
                'intencion' => $intencion->fresh(),
            ]);
        }

        $evidencia = $service->obtenerEvidenciaCaptura(
            $capture,
            $intencion->order_id,
            (string) $intencion->monto_usd,
        );

        if (! $evidencia) {
            $this->cancela($intencion, $user->cliente->usuario->id, $token, 'COMPLETED_MISMATCH');

            return view('recargas.paypal.return', [
                'status' => 'fallida',
                'intencion' => $intencion->fresh(),
            ]);
        }

        try {
            $recarga = $recargaService->procesar(
                referenciaExterna: $intencion->order_id,
                clienteUid: $user->cliente->usuario->uid,
                creditosObtenidos: (int) $intencion->creditos_estimados,
                metodoPago: 'paypal',
                montoUsd: (float) $intencion->monto_usd,
            );
        } catch (Throwable $e) {
            Log::error('Return procesar failed (PayPal)', [
                'order_id' => $token,
                'error' => $e->getMessage(),
            ]);

            return view('recargas.paypal.return', [
                'status' => 'pendiente',
                'intencion' => $intencion,
            ]);
        }

        $recarga->forceFill($evidencia)->save();

        IntencionPaypalTransitions::assert($intencion->estado, EstadoIntencionPaypal::Confirmada);
        $intencion->update(['estado' => EstadoIntencionPaypal::Confirmada]);

        return view('recargas.paypal.return', [
            'status' => 'completada',
            'intencion' => $intencion->fresh(),
            'recarga' => $recarga->fresh(),
        ]);
    }

    public function showCancel(Request $request): View
    {
        $token = $request->query('token');

        $intencion = ($token && is_string($token))
            ? IntencionPaypal::where('order_id', $token)->first()
            : null;

        if ($intencion && $intencion->estado === EstadoIntencionPaypal::Pendiente) {
            IntencionPaypalTransitions::assert($intencion->estado, EstadoIntencionPaypal::Cancelada);
            $intencion->update(['estado' => EstadoIntencionPaypal::Cancelada]);
        }

        return view('recargas.paypal.cancel', [
            'token' => $token,
        ]);
    }

    private function cancela(IntencionPaypal $intencion, int $usuarioId, string $orderId, string $providerStatus): void
    {
        IntencionPaypalTransitions::assert($intencion->estado, EstadoIntencionPaypal::Cancelada);
        $intencion->update(['estado' => EstadoIntencionPaypal::Cancelada]);

        LogActividad::create([
            'accion' => 'recarga.fallida',
            'actor_id' => $usuarioId,
            'detalle' => [
                'referencia_externa' => $orderId,
                'paypal_status' => $providerStatus,
            ],
        ]);
    }
}