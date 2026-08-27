<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoRecarga;
use App\Http\Controllers\Concerns\InteractsWithFinancieroConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AcreditarRecargaRequest;
use App\Http\Requests\Api\RecargaRechazarRequest;
use App\Jobs\SendRecargaRechazadaEmail;
use App\Models\LogActividad;
use App\Models\Recarga;
use App\Models\Usuario;
use App\Services\RecargaService;
use App\StateTransitions\Exceptions\InvalidRecargaTransitionException;
use App\StateTransitions\RecargaTransitions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminRecargaController extends Controller
{
    use InteractsWithFinancieroConfig;

    public function pendientes(Request $request): JsonResponse
    {
        $recargas = Recarga::query()
            ->with(['cliente.usuario'])
            ->where('estado', EstadoRecarga::Pendiente)
            ->whereIn('metodo', ['paypal', 'payphone'])
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

    public function acreditar(AcreditarRecargaRequest $request): JsonResponse
    {
        $staff = $request->user();
        $monto = (float) $request->validated('monto_usd');
        $creditos = (int) floor($monto * $this->getTasaCambioUsdCreditos());
        if ($creditos < 1) {
            throw ValidationException::withMessages([
                'monto_usd' => 'El monto no alcanza para acreditar un crédito.',
            ]);
        }
        $motivo = $request->validated('motivo');
        $referencia = $request->validated('referencia_bancaria');
        $usuario = Usuario::where('email', $request->validated('cliente_email'))
            ->whereHas('cliente')
            ->first();

        if (! $usuario) {
            throw ValidationException::withMessages([
                'cliente_email' => 'No existe un cliente con ese email.',
            ]);
        }

        $existente = Recarga::where('referencia_externa', $referencia)->first();
        if ($existente) {
            if ($existente->cliente_id !== $usuario->cliente->id) {
                return $this->error('La referencia bancaria ya pertenece a otro cliente.', 'REFERENCIA_DUPLICADA', 409);
            }

            if ($existente->estado === EstadoRecarga::Completada) {
                return $this->acreditacionResponse($existente->fresh(['cliente.usuario']));
            }

            return $this->error('La referencia bancaria ya fue registrada.', 'REFERENCIA_DUPLICADA', 409);
        }

        $evidenciaPath = $request->file('comprobante')->store('recargas/comprobantes');

        try {
            $recarga = DB::transaction(function () use ($usuario, $referencia, $monto, $creditos, $evidenciaPath, $staff, $motivo, $request) {
                $recarga = app(RecargaService::class)->procesar(
                    referenciaExterna: $referencia,
                    clienteUid: $usuario->uid,
                    creditosObtenidos: $creditos,
                    metodoPago: 'transferencia',
                    evidenciaPath: $evidenciaPath,
                    montoUsd: $monto,
                );

                LogActividad::create([
                    'accion' => 'recarga.acreditada_manual',
                    'actor_id' => $staff->id,
                    'actor_sistema' => false,
                    'detalle' => [
                        'recarga_id' => $recarga->id,
                        'creditos' => $creditos,
                        'monto_usd' => $monto,
                        'referencia_bancaria' => $referencia,
                        'motivo' => $motivo,
                        'comprobante' => $recarga->comprobante_url,
                    ],
                    'ip_origen' => $request->ip(),
                ]);

                return $recarga;
            });
        } catch (InvalidRecargaTransitionException $e) {
            return $this->error('La recarga no puede acreditarse en su estado actual.', 'TRANSICION_INVALIDA', 409, $e->getMessage());
        }

        return $this->acreditacionResponse($recarga->fresh(['cliente.usuario']));
    }

    private function acreditacionResponse(Recarga $recarga): JsonResponse
    {
        return response()->json([
            'codigo' => 200,
            'exito' => true,
            'mensaje' => 'Recarga acreditada',
            'datos' => ['recarga' => [
                'id' => $recarga->id,
                'estado' => $recarga->estado->value,
                'creditos_obtenidos' => $recarga->creditos_obtenidos,
                'cliente' => [
                    'nombre' => $recarga->cliente?->usuario?->nombre,
                    'email' => $recarga->cliente?->usuario?->email,
                ],
            ]],
            'metadatos' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    private function error(string $mensaje, string $tipo, int $status, ?string $detalle = null): JsonResponse
    {
        return response()->json([
            'codigo' => $status,
            'exito' => false,
            'mensaje' => $mensaje,
            'error' => ['tipo' => $tipo, 'detalle' => $detalle],
            'metadatos' => ['timestamp' => now()->toIso8601String()],
        ], $status);
    }

    public function rechazar(RecargaRechazarRequest $request, Recarga $recarga): JsonResponse
    {
        if (! in_array($recarga->metodo, ['paypal', 'payphone'], true)) {
            return $this->error('Solo se pueden rechazar pagos de pasarela.', 'METODO_NO_AUTORIZADO', 409);
        }

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
