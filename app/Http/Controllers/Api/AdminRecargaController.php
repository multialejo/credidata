<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoRecarga;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RecargaRechazarRequest;
use App\Jobs\SendRecargaRechazadaEmail;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\StateTransitions\Exceptions\InvalidRecargaTransitionException;
use App\StateTransitions\RecargaTransitions;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminRecargaController extends Controller
{
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
