<?php

namespace App\Http\Controllers;

use App\Enums\EstadoIntencionPayphone;
use App\Enums\EstadoRecarga;
use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use App\Models\IntencionPayphone;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Services\RecargaPayphoneService;
use App\Services\RecargaService;
use App\StateTransitions\IntencionPayphoneTransitions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class RecargaPayphoneReturnController extends Controller
{
    use InteractsWithFinancieroConfig;

    public function showReturn(
        Request $request,
        RecargaPayphoneService $service,
        RecargaService $recargaService,
    ): View {
        $paymentId = $request->query('id');
        $ctid = $request->query('clientTransactionId');

        $intencion = ($ctid && is_string($ctid))
            ? IntencionPayphone::where('ctid', $ctid)->first()
            : null;

        // Sin intención, sin paymentId, o usuario autenticado que no es el dueño.
        // Misma vista byte-identical que ctid inexistente (no info leak).
        if (! $intencion || ! $paymentId) {
            return view('recargas.payphone.return', [
                'status' => 'not_found',
                'intencion' => null,
            ]);
        }

        $user = $request->user();
        if ($user && $intencion->cliente_id !== $user->cliente?->id) {
            return view('recargas.payphone.return', [
                'status' => 'not_found',
                'intencion' => null,
            ]);
        }

        // Idempotencia: el ctid ya fue acreditado, no re-confirmar ni re-acreditar.
        $recargaCompletada = Recarga::where('referencia_externa', $ctid)
            ->where('estado', EstadoRecarga::Completada)
            ->first();

        if ($recargaCompletada) {
            return view('recargas.payphone.return', [
                'status' => 'completada',
                'intencion' => $intencion,
                'recarga' => $recargaCompletada,
            ]);
        }

        // Intención ya confirmada (recarga confirmada ausente por un caso raro).
        if ($intencion->estado === EstadoIntencionPayphone::Confirmada) {
            return view('recargas.payphone.return', [
                'status' => 'completada',
                'intencion' => $intencion,
                'recarga' => null,
            ]);
        }

        // Cancelada o expirada: terminal, no reintentar.
        if (in_array($intencion->estado, [EstadoIntencionPayphone::Cancelada, EstadoIntencionPayphone::Expirada], true)) {
            return view('recargas.payphone.return', [
                'status' => 'fallida',
                'intencion' => $intencion,
            ]);
        }

        // Pendiente + guest, mostrar status solamente.
        if (! $user) {
            return view('recargas.payphone.return', [
                'status' => 'pendiente',
                'intencion' => $intencion,
            ]);
        }

        // Pendiente + autenticado + dueño, confirmar + validar monto + acreditar.
        try {
            $confirm = $service->confirm((int) $paymentId, $ctid, (float) $intencion->monto_usd);
        } catch (ConnectionException $e) {
            Log::error('Return confirm failed (Payphone connection)', [
                'clientTransactionId' => $ctid,
                'id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return view('recargas.payphone.return', [
                'status' => 'pendiente',
                'intencion' => $intencion,
            ]);
        }

        $status = $confirm['transactionStatus'] ?? 'Unknown';

        if ($status !== 'Approved') {
            IntencionPayphoneTransitions::assert($intencion->estado, EstadoIntencionPayphone::Cancelada);
            $intencion->update(['estado' => EstadoIntencionPayphone::Cancelada]);
            LogActividad::create([
                'accion' => 'recarga.fallida',
                'actor_id' => $user->cliente->usuario->id,
                'detalle' => [
                    'referencia_externa' => $ctid,
                    'payphone_status' => $status,
                    'payphone_message' => $confirm['message'] ?? null,
                ],
            ]);

            return view('recargas.payphone.return', [
                'status' => 'fallida',
                'intencion' => $intencion->fresh(),
            ]);
        }

        $montoPagado = round((float) ($confirm['amountPaidUsd'] ?? 0), 2);
        $montoEsperado = round((float) $intencion->monto_usd, 2);

        if ($montoPagado !== $montoEsperado) {
            IntencionPayphoneTransitions::assert($intencion->estado, EstadoIntencionPayphone::Cancelada);
            $intencion->update(['estado' => EstadoIntencionPayphone::Cancelada]);
            LogActividad::create([
                'accion' => 'recarga.fallida',
                'actor_id' => $user->cliente->usuario->id,
                'detalle' => [
                    'referencia_externa' => $ctid,
                    'payphone_status' => $status,
                    'payphone_message' => "Monto pagado {$montoPagado} USD no coincide con la intención de {$montoEsperado} USD",
                ],
            ]);

            return view('recargas.payphone.return', [
                'status' => 'fallida',
                'intencion' => $intencion->fresh(),
            ]);
        }

        try {
            $recarga = $recargaService->procesar(
                referenciaExterna: $intencion->ctid,
                clienteUid: $user->cliente->usuario->uid,
                creditosObtenidos: (int) $intencion->creditos_estimados,
                metodoPago: 'payphone',
                montoUsd: $montoEsperado,
            );
        } catch (Throwable $e) {
            Log::error('Return procesar failed (Payphone)', [
                'clientTransactionId' => $ctid,
                'error' => $e->getMessage(),
            ]);

            return view('recargas.payphone.return', [
                'status' => 'pendiente',
                'intencion' => $intencion,
            ]);
        }

        $recarga->forceFill([
            'provider_payment_id' => (string) $paymentId,
            'provider_transaction_id' => $confirm['transactionId'] ?? null,
            'provider_authorization_code' => $confirm['authorizationCode'] ?? null,
            'provider_status' => $status,
            'provider_currency' => config('payphone.currency'),
            'provider_verified_at' => now(),
        ])->save();

        IntencionPayphoneTransitions::assert($intencion->estado, EstadoIntencionPayphone::Confirmada);
        $intencion->update(['estado' => EstadoIntencionPayphone::Confirmada]);

        return view('recargas.payphone.return', [
            'status' => 'completada',
            'intencion' => $intencion->fresh(),
            'recarga' => $recarga->fresh(),
        ]);
    }

    public function showCancel(Request $request): View
    {
        $ctid = $request->query('clientTransactionId');

        $intencion = ($ctid && is_string($ctid))
            ? IntencionPayphone::where('ctid', $ctid)->first()
            : null;

        if ($intencion && $intencion->estado === EstadoIntencionPayphone::Pendiente) {
            IntencionPayphoneTransitions::assert($intencion->estado, EstadoIntencionPayphone::Cancelada);
            $intencion->update(['estado' => EstadoIntencionPayphone::Cancelada]);
        }

        return view('recargas.payphone.cancel', [
            'id' => $request->query('id'),
            'clientTransactionId' => $ctid,
        ]);
    }
}
