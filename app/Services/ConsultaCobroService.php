<?php

namespace App\Services;

use App\Exceptions\SaldoInsuficienteException;
use App\Models\ApiKey;
use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\Consulta;
use App\Models\LogActividad;
use Illuminate\Support\Facades\DB;

/**
 * Único punto de cobro de consultas: bloqueo de fila, revalidación de saldo,
 * débito, Consulta y LogActividad en una sola transacción (REQ §7 Trazabilidad).
 */
class ConsultaCobroService
{
    private const ACCIONES = ['cedula' => 'CONSULTA_CEDULA', 'ruc' => 'CONSULTA_RUC'];

    public function costoConsulta(): int
    {
        $param = ConfigParametro::query()
            ->where('modulo', 'financiero')
            ->where('clave', 'costoConsultaBase')
            ->first();

        return $param ? (int) json_decode($param->valor) : 1;
    }

    /**
     * @param  list<string>  $fuentes
     *
     * @throws SaldoInsuficienteException si el saldo bajo bloqueo no cubre el costo.
     */
    public function cobrar(Cliente $cliente, ApiKey $apiKey, string $tipo, string $identificador, int $costo, ?array $resultado, array $fuentes, string $ip): ResultadoCobro
    {
        return DB::transaction(function () use ($cliente, $apiKey, $tipo, $identificador, $costo, $resultado, $fuentes, $ip): ResultadoCobro {
            $bloqueado = Cliente::query()->lockForUpdate()->findOrFail($cliente->id);
            $saldoAnterior = (float) $bloqueado->saldo_creditos;

            if ($saldoAnterior < $costo) {
                throw new SaldoInsuficienteException($saldoAnterior, $costo);
            }

            $bloqueado->decrement('saldo_creditos', $costo);
            $saldoNuevo = $saldoAnterior - $costo;

            $consulta = $this->registrar($bloqueado, $apiKey, $tipo, $identificador, $costo, true, $resultado, $fuentes, $saldoAnterior, $saldoNuevo, $ip);

            return new ResultadoCobro($consulta, $saldoAnterior, $saldoNuevo);
        });
    }

    /**
     * Registra una consulta no cobrable (fuente externa caída): 0 créditos, sin tocar saldo.
     */
    public function registrarSinCobro(Cliente $cliente, ApiKey $apiKey, string $tipo, string $identificador, string $ip): ResultadoCobro
    {
        return DB::transaction(function () use ($cliente, $apiKey, $tipo, $identificador, $ip): ResultadoCobro {
            $saldo = (float) Cliente::query()->whereKey($cliente->id)->value('saldo_creditos');

            $consulta = $this->registrar($cliente, $apiKey, $tipo, $identificador, 0, false, null, [], $saldo, $saldo, $ip);

            return new ResultadoCobro($consulta, $saldo, $saldo);
        });
    }

    private function registrar(Cliente $cliente, ApiKey $apiKey, string $tipo, string $identificador, int $creditos, bool $exitosa, ?array $resultado, array $fuentes, float $saldoAnterior, float $saldoNuevo, string $ip): Consulta
    {
        $consulta = Consulta::create([
            'cliente_id' => $cliente->id,
            'api_key_id' => $apiKey->id,
            'tipo' => $tipo,
            'identificador' => $identificador,
            'creditos_gastados' => $creditos,
            'resultado_json' => $resultado,
            'fuentes_utilizadas' => $fuentes ?: null,
            'exitosa' => $exitosa,
            'ip_origen' => $ip,
            'origen' => 'api',
        ]);

        LogActividad::create([
            'accion' => self::ACCIONES[$tipo],
            'actor_id' => $cliente->usuario_id,
            'detalle' => [
                'consulta_id' => $consulta->id,
                $tipo => $identificador,
                'api_key_id' => $apiKey->id,
                'creditos_gastados' => $creditos,
                'saldo_anterior' => $saldoAnterior,
                'saldo_nuevo' => $saldoNuevo,
                'motivo' => "consulta.{$tipo}",
            ],
            'ip_origen' => $ip,
        ]);

        return $consulta;
    }
}
