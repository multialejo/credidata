<?php

namespace App\Http\Controllers;

use App\Enums\EstadoRecarga;
use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Services\RecargaPayphoneService;
use App\Services\RecargaService;
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

        $recarga = ($ctid && is_string($ctid))
            ? Recarga::where('referencia_externa', $ctid)->first()
            : null;

        // Sin recarga, falta el paymentId, o usuario autenticado pero no es el dueno del pedido.
        // Misma vista byte-identical que ctid inexistente (no info leak).
        if (! $recarga || ! $paymentId) {
            return view('recargas.payphone.return', [
                'status' => 'not_found',
                'recarga' => null,
            ]);
        }

        $user = $request->user();
        if ($user && $recarga->cliente_id !== $user->cliente?->id) {
            return view('recargas.payphone.return', [
                'status' => 'not_found',
                'recarga' => null,
            ]);
        }

        // Ya completada, no re-confirmar.
        if ($recarga->estado === EstadoRecarga::Completada) {
            return view('recargas.payphone.return', [
                'status' => 'completada',
                'recarga' => $recarga,
            ]);
        }

        // Fallida o rechazada (terminal), no reintentar.
        if (in_array($recarga->estado, [EstadoRecarga::Fallida, EstadoRecarga::Rechazada], true)) {
            return view('recargas.payphone.return', [
                'status' => $recarga->estado->value,
                'recarga' => $recarga,
            ]);
        }

        // Pendiente + guest, mostrar status solamente.
        if (! $user) {
            return view('recargas.payphone.return', [
                'status' => 'pendiente',
                'recarga' => $recarga,
            ]);
        }

        // Pendiente + autenticado + dueno, confirmar + acreditar.
        try {
            $confirm = $service->confirm((int) $paymentId, $ctid);
        } catch (ConnectionException $e) {
            Log::error('Return confirm failed (Payphone connection)', [
                'clientTransactionId' => $ctid,
                'id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return view('recargas.payphone.return', [
                'status' => 'pendiente',
                'recarga' => $recarga,
            ]);
        }

        $status = $confirm['transactionStatus'] ?? 'Unknown';

        if ($status !== 'Approved') {
            $recarga->update(['estado' => EstadoRecarga::Fallida]);
            LogActividad::create([
                'accion' => 'recarga.fallida',
                'actor_id' => $user->cliente->usuario->id,
                'detalle' => [
                    'referencia_externa' => $ctid,
                    'payphone_status' => $status,
                    'payphone_message' => $confirm['message'] ?? null,
                ],
            ]);
            $recarga->refresh();

            return view('recargas.payphone.return', [
                'status' => 'fallida',
                'recarga' => $recarga,
            ]);
        }

        $creditos = (int) $recarga->creditos_obtenidos;

        try {
            $recargaService->procesar(
                referenciaExterna: $recarga->referencia_externa,
                clienteUid: $user->cliente->usuario->uid,
                creditosObtenidos: $creditos,
                metodoPago: 'payphone',
            );
        } catch (Throwable $e) {
            Log::error('Return procesar failed (Payphone)', [
                'clientTransactionId' => $ctid,
                'error' => $e->getMessage(),
            ]);
        }

        $recarga->refresh();

        return view('recargas.payphone.return', [
            'status' => 'completada',
            'recarga' => $recarga,
        ]);
    }

    public function showCancel(Request $request): View
    {
        return view('recargas.payphone.cancel', [
            'id' => $request->query('id'),
            'clientTransactionId' => $request->query('clientTransactionId'),
        ]);
    }
}
