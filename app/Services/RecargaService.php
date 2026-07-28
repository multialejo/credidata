<?php

namespace App\Services;

use App\Jobs\SendRecargaEmail;
use App\Models\Cliente;
use App\Models\LogActividad;
use App\Models\Recarga;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RecargaService
{
    /**
     * Acredita créditos al cliente de forma idempotente y atómica.
     *
     * @param  string  $referenciaExterna  ID externo (Payphone clientTransactionId,
     *                                     PayPal order_id, o UUID interno de la
     *                                     solicitud de depósito). Clave de
     *                                     idempotencia en `recargas.referencia_externa`.
     * @param  string  $clienteUid  UID público del `Cliente` que recibe los créditos.
     * @param  int  $creditosObtenidos  Créditos a acreditar (entero >= 1). Ya
     *                                  calculado server-side; el servicio NO
     *                                  recalcula ni acepta valores del request.
     * @param  string  $metodoPago  'payphone' | 'paypal' | 'transferencia' | 'bonificacion'.
     *                              String opaco — el servicio no valida el set.
     * @param  string|null  $evidenciaPath  Path al comprobante en storage (solo para
     *                                      `transferencia`; null en el resto).
     * @return Recarga Modelo con la fila recién creada/recuperada.
     *
     * @throws RuntimeException Si la transacción MySQL falla.
     */
    public function procesar(
        string $referenciaExterna,
        string $clienteUid,
        int $creditosObtenidos,
        string $metodoPago,
        ?string $evidenciaPath = null,
    ): Recarga {
        $cliente = Cliente::whereHas('usuario', fn ($q) =>
            $q->where('uid', $clienteUid)
        )->firstOrFail();

        if (Recarga::where('referencia_externa', $referenciaExterna)->where('estado', 'completada')->exists()) {
            return Recarga::where('referencia_externa', $referenciaExterna)->first();
        }

        return DB::transaction(function () use (
            $referenciaExterna, $cliente, $creditosObtenidos,
            $metodoPago, $evidenciaPath,
        ) {
            $recarga = Recarga::lockForUpdate()
                ->where('referencia_externa', $referenciaExterna)
                ->first();

            if ($recarga !== null && $recarga->estado === 'completada') {
                return $recarga;
            }

            if ($recarga === null) {
                $recarga = Recarga::create([
                    'cliente_id' => $cliente->id,
                    'metodo' => $metodoPago,
                    'monto_usd' => 0,
                    'creditos_obtenidos' => $creditosObtenidos,
                    'estado' => 'completada',
                    'referencia_externa' => $referenciaExterna,
                    'comprobante_url' => $evidenciaPath,
                    'fecha' => now(),
                ]);
            } else {
                $recarga->update([
                    'cliente_id' => $cliente->id,
                    'metodo' => $metodoPago,
                    'creditos_obtenidos' => $creditosObtenidos,
                    'comprobante_url' => $evidenciaPath,
                    'estado' => 'completada',
                ]);
                $recarga->refresh();
            }

            $cliente->newQuery()
                ->where('id', $cliente->id)
                ->update([
                    'saldo_creditos' => DB::raw("saldo_creditos + {$creditosObtenidos}"),
                ]);

            $this->recordLog([
                'accion' => 'recarga.acreditada',
                'actor_id' => $cliente->usuario->id,
                'detalle' => [
                    'recarga_id' => $recarga->id,
                    'metodo' => $metodoPago,
                    'creditos' => $creditosObtenidos,
                    'referencia_externa' => $referenciaExterna,
                ],
            ]);

            DB::afterCommit(function () use ($recarga) {
                dispatch(new SendRecargaEmail($recarga));
            });

            return $recarga;
        });
    }

    protected function recordLog(array $data): LogActividad
    {
        return LogActividad::create($data);
    }
}
