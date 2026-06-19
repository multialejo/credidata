<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\Consulta;
use App\Models\LogActividad;
use App\Services\DinardapService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsultaController extends Controller
{
    public function __construct(
        private DinardapService $dinardapService,
    ) {}

    public function consultaCedula(Request $request)
    {
        $request->validate([
            'cedula' => ['required', 'string', 'digits_between:8,13'],
        ]);

        $cliente = $request->cliente_autenticado;

        if (!$cliente) {
            return response()->json([
                'codigo' => 401,
                'exito' => false,
                'mensaje' => 'API Key inválida o revocada',
                'error' => ['tipo' => 'API_KEY_INVALIDA', 'detalle' => null],
                'datos' => null,
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 401);
        }

        $cedula = $request->cedula;
        $costo = $this->getCostoConsulta();

        // 1. Validate saldo
        if ($cliente->saldo_creditos < $costo) {
            return response()->json([
                'codigo' => 402,
                'exito' => false,
                'mensaje' => 'Saldo insuficiente para realizar la consulta.',
                'error' => [
                    'tipo' => 'SALDO_INSUFICIENTE',
                    'detalle' => "El saldo actual es {$cliente->saldo_creditos} créditos. Se requiere al menos {$costo} crédito(s).",
                ],
                'datos' => null,
                'metadatos' => [
                    'timestamp' => now()->toIso8601String(),
                    'creditos_restantes' => (int) $cliente->saldo_creditos,
                ],
            ], 402);
        }

        // 2. Check Firestore cache
        $cache = $this->dinardapService->obtenerCache($cedula);
        if ($cache !== null) {
            DB::transaction(function () use ($cliente, $costo, $cedula, $cache, $request, &$consulta) {
                $this->debitarCliente($cliente, $costo);
                $consulta = $this->registrarConsulta($cliente, $cedula, $costo, true, $cache, $request->ip());
                $this->registrarLog($cliente, $consulta->id, $cedula, $costo, $request->ip());
            });

            return response()->json([
                'codigo' => 200,
                'exito' => true,
                'mensaje' => 'Consulta exitosa',
                'datos' => $cache,
                'metadatos' => [
                    'timestamp' => now()->toIso8601String(),
                    'consulta_id' => $consulta->id,
                    'cedula' => $cedula,
                    'creditos_gastados' => $costo,
                    'creditos_restantes' => (int) $cliente->saldo_creditos,
                    'fuente' => 'cache',
                ],
            ]);
        }

        // 3. Call Dinardap external API
        try {
            $resultado = $this->dinardapService->consultar($cedula);
        } catch (ConnectionException $e) {
            return $this->respondFuenteNoDisponible($cliente, $cedula, $costo, $request->ip());
        }

        if ($resultado['status'] === 'not_found') {
            return $this->respondSujetoNoEncontrado($cliente, $cedula, $costo, $request->ip());
        }

        // 4. Success
        $this->dinardapService->guardarCache($cedula, $resultado['data']);

        DB::transaction(function () use ($cliente, $costo, $cedula, $resultado, $request, &$consulta) {
            $this->debitarCliente($cliente, $costo);
            $consulta = $this->registrarConsulta($cliente, $cedula, $costo, true, $resultado['data'], $request->ip());
            $this->registrarLog($cliente, $consulta->id, $cedula, $costo, $request->ip());
        });

        return response()->json([
            'codigo' => 200,
            'exito' => true,
            'mensaje' => 'Consulta exitosa',
            'datos' => $resultado['data'],
            'metadatos' => [
                'timestamp' => now()->toIso8601String(),
                'consulta_id' => $consulta->id,
                'cedula' => $cedula,
                'creditos_gastados' => $costo,
                'creditos_restantes' => (int) $cliente->saldo_creditos,
                'fuente' => 'dinardap',
            ],
        ]);
    }

    private function getCostoConsulta(): int
    {
        $param = ConfigParametro::where('clave', 'costoConsultaBase')->first();

        return $param ? (int) json_decode($param->valor) : 1;
    }

    private function debitarCliente(Cliente $cliente, int $costo): void
    {
        $cliente->timestamps = false;
        $cliente->updateQuietly(['api_key_ultimo_uso' => now()]);
        $cliente->decrement('saldo_creditos', $costo);
        $cliente->refresh();
    }

    private function registrarConsulta(Cliente $cliente, string $cedula, int $creditosGastados, bool $exitosa, ?array $datos, string $ip): Consulta
    {
        return Consulta::create([
            'cliente_id' => $cliente->uid,
            'tipo' => 'cedula',
            'identificador' => $cedula,
            'creditos_gastados' => $creditosGastados,
            'resultado_json' => $datos,
            'fuentes_utilizadas' => $datos !== null ? ['dinardap'] : null,
            'exitosa' => $exitosa,
            'ip_origen' => $ip,
            'origen' => 'api',
        ]);
    }

    private function registrarLog(Cliente $cliente, ?int $consultaId, string $cedula, int $creditosGastados, string $ip): void
    {
        LogActividad::create([
            'accion' => 'CONSULTA_CEDULA',
            'actor_id' => $cliente->uid,
            'detalle' => [
                'consulta_id' => $consultaId,
                'cedula' => $cedula,
                'creditos_gastados' => $creditosGastados,
            ],
            'ip_origen' => $ip,
        ]);
    }

    private function respondFuenteNoDisponible(Cliente $cliente, string $cedula, int $costo, string $ip)
    {
        $consulta = null;

        DB::transaction(function () use ($cliente, $cedula, $ip, &$consulta) {
            $consulta = $this->registrarConsulta($cliente, $cedula, 0, false, null, $ip);
            $this->registrarLog($cliente, $consulta->id, $cedula, 0, $ip);
        });

        return response()->json([
            'codigo' => 503,
            'exito' => false,
            'mensaje' => 'La fuente de datos externa no está disponible en este momento',
            'error' => [
                'tipo' => 'FUENTE_EXTERNA_NO_DISPONIBLE',
                'detalle' => 'No se pudo conectar con Dinardap',
            ],
            'datos' => null,
            'metadatos' => [
                'timestamp' => now()->toIso8601String(),
                'creditos_gastados' => 0,
                'creditos_restantes' => (int) $cliente->saldo_creditos,
            ],
        ], 503);
    }

    private function respondSujetoNoEncontrado(Cliente $cliente, string $cedula, int $costo, string $ip)
    {
        $consulta = null;

        DB::transaction(function () use ($cliente, $costo, $cedula, $ip, &$consulta) {
            $this->debitarCliente($cliente, $costo);
            $consulta = $this->registrarConsulta($cliente, $cedula, $costo, true, null, $ip);
            $this->registrarLog($cliente, $consulta->id, $cedula, $costo, $ip);
        });

        return response()->json([
            'codigo' => 404,
            'exito' => false,
            'mensaje' => 'No se encontraron datos para el identificador ingresado',
            'error' => [
                'tipo' => 'SUJETO_NO_ENCONTRADO',
                'detalle' => 'La cédula no tiene registros en Dinardap',
            ],
            'datos' => null,
            'metadatos' => [
                'timestamp' => now()->toIso8601String(),
                'creditos_gastados' => $costo,
                'creditos_restantes' => (int) $cliente->saldo_creditos,
            ],
        ], 404);
    }
}
