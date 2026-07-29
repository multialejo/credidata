<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Services\RecargaPaypalService;
use App\Services\RecargaService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class RecargaPaypalReturnController extends Controller
{
    use InteractsWithFinancieroConfig;

    public function showReturn(
        Request $request,
        RecargaPaypalService $service,
        RecargaService $recargaService,
    ): View {
        $token = $request->query('token');
        $user = $request->user();

        $recarga = $token
            ? Recarga::where('referencia_externa', $token)->first()
            : null;

        // Sin recarga, o usuario autenticado pero no es el dueno del pedido.
        // REQ-04: misma vista byte-identical que token inexistente.
        if (! $recarga) {
            return view('recargas.paypal.return', [
                'status' => 'not_found',
                'recarga' => null,
            ]);
        }

        if ($user && $recarga->cliente_id !== $user->cliente?->id) {
            return view('recargas.paypal.return', [
                'status' => 'not_found',
                'recarga' => null,
            ]);
        }

        // REQ-01: ya completada, no re-capturar.
        if ($recarga->estado === 'completada') {
            return view('recargas.paypal.return', [
                'status' => 'completada',
                'recarga' => $recarga,
            ]);
        }

        // REQ-05: fallida, no reintentar.
        if ($recarga->estado === 'fallida') {
            return view('recargas.paypal.return', [
                'status' => 'fallida',
                'recarga' => $recarga,
            ]);
        }

        // REQ-03: pendiente + guest, mostrar status solamente.
        if (! $user) {
            return view('recargas.paypal.return', [
                'status' => 'pendiente',
                'recarga' => $recarga,
            ]);
        }

        // REQ-02: pendiente + autenticado + dueno, capturar + acreditar.
        try {
            $capture = $service->captureOrder($recarga->referencia_externa);
        } catch (ConnectionException $e) {
            Log::error('Return capture failed (PayPal connection)', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return view('recargas.paypal.return', [
                'status' => 'pendiente',
                'recarga' => $recarga,
            ]);
        }

        if (($capture['status'] ?? null) !== 'COMPLETED') {
            $recarga->update(['estado' => 'fallida']);
            LogActividad::create([
                'accion' => 'recarga.fallida',
                'actor_id' => $user->cliente->usuario->id,
                'detalle' => [
                    'referencia_externa' => $token,
                    'paypal_status' => $capture['status'] ?? 'unknown',
                ],
            ]);
            $recarga->refresh();

            return view('recargas.paypal.return', [
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
                metodoPago: 'paypal',
            );
        } catch (\Throwable $e) {
            Log::error('Return procesar failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
        }

        $recarga->refresh();

        return view('recargas.paypal.return', [
            'status' => 'completada',
            'recarga' => $recarga,
        ]);
    }

    public function showCancel(Request $request): View
    {
        return view('recargas.paypal.cancel', [
            'token' => $request->query('token'),
        ]);
    }
}
