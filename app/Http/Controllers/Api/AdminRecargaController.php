<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoRecarga;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AcreditarRecargaRequest;
use App\Http\Requests\Api\RecargaRechazarRequest;
use App\Jobs\SendRecargaRechazadaEmail;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Services\RecargaService;
use App\StateTransitions\Exceptions\InvalidRecargaTransitionException;
use App\StateTransitions\RecargaTransitions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminRecargaController extends Controller
{
    public function pendientes(Request $request): JsonResponse
    {
        $recargas = Recarga::query()
            ->with(['cliente.usuario'])
            ->where('estado', EstadoRecarga::Pendiente)
            ->latest('fecha')
            ->paginate(15);

        return response()->json([
            'codigo' => 200,
            'exito' => true,
            'mensaje' => 'Recargas pendientes',
            'datos' => [
                'recargas' => $recargas->through(fn (Recarga $r) => [
                    'id' => $r->id,
                    'fecha' => $r->fecha?->toIso8601String(),
                    'metodo' => $r->metodo,
                    'monto_usd' => (float) $r->monto_usd,
                    'creditos_obtenidos' => $r->creditos_obtenidos,
                    'estado' => $r->estado->value,
                    'referencia_externa' => $r->referencia_externa,
                    'comprobante_url' => $r->comprobante_url,
                    'cliente' => [
                        'nombre' => $r->cliente?->usuario?->nombre,
                        'email' => $r->cliente?->usuario?->email,
                    ],
                ])->items(),
                'paginacion' => [
                    'total' => $recargas->total(),
                    'per_page' => $recargas->perPage(),
                    'current_page' => $recargas->currentPage(),
                    'last_page' => $recargas->lastPage(),
                ],
            ],
            'metadatos' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function acreditar(AcreditarRecargaRequest $request, Recarga $recarga): JsonResponse
    {
        $staff = $request->user();
        $creditos = $request->validated('creditos');
        $motivo = $request->validated('motivo');

        try {
            DB::transaction(function () use ($recarga, $staff, $creditos, $motivo, $request) {
                $locked = Recarga::lockForUpdate()->findOrFail($recarga->id);

                RecargaTransitions::assert(
                    $locked->estado,
                    EstadoRecarga::Completada,
                );

                // RecargaService::procesar es un método de instancia (la firma
                // real difiere del plan: parámetros camelCase y evidenciaPath).
                app(RecargaService::class)->procesar(
                    referenciaExterna: $locked->referencia_externa,
                    clienteUid: $locked->cliente->usuario->uid,
                    creditosObtenidos: $creditos,
                    metodoPago: 'transferencia',
                    evidenciaPath: $locked->comprobante_url,
                );

                LogActividad::create([
                    'accion' => 'recarga.acreditada_manual',
                    'actor_id' => $staff->id,
                    'actor_sistema' => false,
                    'detalle' => [
                        'recarga_id' => $locked->id,
                        'creditos' => $creditos,
                        'motivo' => $motivo,
                    ],
                    'ip_origen' => $request->ip(),
                ]);
            });
        } catch (InvalidRecargaTransitionException $e) {
            return response()->json([
                'codigo' => 409,
                'exito' => false,
                'mensaje' => 'La recarga no puede acreditarse en su estado actual',
                'error' => ['tipo' => 'TRANSICION_INVALIDA', 'detalle' => $e->getMessage()],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 409);
        }

        $recarga->refresh();
        $recarga->load(['cliente.usuario']);

        return response()->json([
            'codigo' => 200,
            'exito' => true,
            'mensaje' => 'Recarga acreditada',
            'datos' => [
                'recarga' => [
                    'id' => $recarga->id,
                    'estado' => $recarga->estado->value,
                    'creditos_obtenidos' => $recarga->creditos_obtenidos,
                    'cliente' => [
                        'nombre' => $recarga->cliente?->usuario?->nombre,
                        'email' => $recarga->cliente?->usuario?->email,
                    ],
                ],
            ],
            'metadatos' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function rechazar(RecargaRechazarRequest $request, Recarga $recarga): JsonResponse
    {
        $staff = $request->user();
        $motivo = $request->validated('motivo');

        try {
            DB::transaction(function () use ($recarga, $staff, $motivo) {
                $locked = Recarga::lockForUpdate()->findOrFail($recarga->id);

                RecargaTransitions::assert(
                    $locked->estado,
                    EstadoRecarga::Rechazada,
                );

                $locked->update([
                    'estado' => EstadoRecarga::Rechazada,
                    'motivo_rechazo' => $motivo,
                    'rechazada_por' => $staff->id,
                    'rechazada_at' => now(),
                ]);

                LogActividad::create([
                    'accion' => 'recarga.rechazada',
                    'actor_id' => $staff->id,
                    'detalle' => [
                        'recarga_id' => $locked->id,
                        'motivo' => $motivo,
                        'referencia_externa' => $locked->referencia_externa,
                    ],
                ]);
            });
        } catch (InvalidRecargaTransitionException $e) {
            return response()->json([
                'codigo' => 409,
                'exito' => false,
                'mensaje' => 'La recarga no puede ser rechazada en su estado actual',
                'error' => ['tipo' => 'TRANSICION_INVALIDA', 'detalle' => $e->getMessage()],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 409);
        }

        $recarga->refresh();
        $recarga->load(['rechazadaPor', 'cliente']);

        DB::afterCommit(function () use ($recarga) {
            dispatch(new SendRecargaRechazadaEmail($recarga));
        });

        return response()->json([
            'codigo' => 200,
            'exito' => true,
            'mensaje' => 'Recarga rechazada',
            'datos' => [
                'recarga' => [
                    'id' => $recarga->id,
                    'estado' => $recarga->estado->value,
                    'motivo_rechazo' => $recarga->motivo_rechazo,
                    'rechazada_por' => $recarga->rechazadaPor?->nombre,
                    'rechazada_at' => $recarga->rechazada_at?->toIso8601String(),
                ],
            ],
            'metadatos' => ['timestamp' => now()->toIso8601String()],
        ]);
    }
}
