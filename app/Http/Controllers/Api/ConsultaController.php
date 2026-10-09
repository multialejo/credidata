<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\SaldoInsuficienteException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ConsultaResource;
use App\Http\Resources\ConsultaRucResource;
use App\Models\ApiKey;
use App\Models\Cliente;
use App\Rules\EcuadorianIdentificador;
use App\Services\CatastroService;
use App\Services\ConsultaCobroService;
use App\Services\DinardapService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConsultaController extends Controller
{
    public function __construct(
        private DinardapService $dinardapService,
        private CatastroService $catastroService,
        private ConsultaCobroService $cobroService,
    ) {}

    public function consultaCedula(Request $request)
    {
        $request->validate([
            'cedula' => ['required', 'string', new EcuadorianIdentificador],
        ]);

        $cliente = $request->cliente_autenticado;

        if (! $cliente) {
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
        $apiKey = $request->api_key_autenticada;
        $costo = $this->cobroService->costoConsulta();

        // 1. Pre-validate saldo (fast-fail); the locked revalidation in the service is authoritative
        if ($cliente->saldo_creditos < $costo) {
            return $this->respondSaldoInsuficiente((float) $cliente->saldo_creditos, $costo);
        }

        // 2. Check Firestore cache
        $cache = $this->dinardapService->obtenerCache($cedula);
        if ($cache !== null) {
            try {
                $cobro = $this->cobroService->cobrar($cliente, $apiKey, 'cedula', $cedula, $costo, $cache, ['dinardap'], $request->ip());
            } catch (SaldoInsuficienteException $e) {
                return $this->respondSaldoInsuficiente($e->saldoActual, $e->costo);
            }

            return response()->json([
                'codigo' => 200,
                'exito' => true,
                'mensaje' => 'Consulta exitosa',
                'datos' => new ConsultaResource(array_merge($cache, ['cedula' => $cedula])),
                'metadatos' => [
                    'timestamp' => now()->toIso8601String(),
                    'creditos_gastados' => $costo,
                    'creditos_restantes' => (int) $cobro->saldoNuevo,
                    'fuente' => 'cache',
                ],
            ]);
        }

        // 3. Call Dinardap external API
        try {
            $resultado = $this->dinardapService->consultar($cedula);
        } catch (ConnectionException $e) {
            return $this->respondFuenteNoDisponible($cliente, $apiKey, $cedula, $request->ip());
        }

        if ($resultado['status'] === 'not_found') {
            return $this->respondSujetoNoEncontrado($cliente, $apiKey, $cedula, $costo, $request->ip());
        }

        // 4. Success: the data is valid, so it is cached even if the charge below ends in 402
        $this->dinardapService->guardarCache($cedula, $resultado['data']);

        try {
            $cobro = $this->cobroService->cobrar($cliente, $apiKey, 'cedula', $cedula, $costo, $resultado['data'], ['dinardap'], $request->ip());
        } catch (SaldoInsuficienteException $e) {
            return $this->respondSaldoInsuficiente($e->saldoActual, $e->costo);
        }

        return response()->json([
            'codigo' => 200,
            'exito' => true,
            'mensaje' => 'Consulta exitosa',
            'datos' => new ConsultaResource(array_merge($resultado['data'], ['cedula' => $cedula])),
            'metadatos' => [
                'timestamp' => now()->toIso8601String(),
                'creditos_gastados' => $costo,
                'creditos_restantes' => (int) $cobro->saldoNuevo,
                'fuente' => 'dinardap',
            ],
        ]);
    }

    public function consultaRuc(Request $request)
    {
        $request->validate([
            'ruc' => ['required', 'string', 'size:13', 'regex:/^[0-9]+$/', new EcuadorianIdentificador],
        ]);

        $cliente = $request->cliente_autenticado;
        $apiKey = $request->api_key_autenticada;
        $ruc = $request->string('ruc')->toString();
        $costo = $this->cobroService->costoConsulta();

        if (! $cliente) {
            return response()->json(['codigo' => 401, 'exito' => false, 'mensaje' => 'API Key inválida o revocada', 'datos' => null], 401);
        }
        if ($cliente->saldo_creditos < $costo) {
            return $this->respondSaldoInsuficiente((float) $cliente->saldo_creditos, $costo);
        }

        try {
            $establecimientos = $this->catastroService->buscarEstablecimientos($ruc);
        } catch (Throwable $e) {
            Log::warning('Catastro Firestore no disponible', ['ruc' => $ruc, 'error' => $e->getMessage()]);

            return response()->json([
                'codigo' => 503, 'exito' => false,
                'mensaje' => 'La fuente de datos externa no está disponible en este momento',
                'error' => ['tipo' => 'FUENTE_EXTERNA_NO_DISPONIBLE', 'detalle' => null], 'datos' => null,
                'metadatos' => ['timestamp' => now()->toIso8601String(), 'creditos_gastados' => 0, 'creditos_restantes' => (int) $cliente->saldo_creditos],
            ], 503);
        }

        $encontrado = count($establecimientos) > 0;
        try {
            $cobro = $this->cobroService->cobrar(
                $cliente, $apiKey, 'ruc', $ruc, $costo,
                ['ruc' => $ruc, 'establecimientos' => $establecimientos], ['catastro_sri'], $request->ip(),
            );
        } catch (SaldoInsuficienteException $e) {
            return $this->respondSaldoInsuficiente($e->saldoActual, $e->costo);
        }

        return response()->json([
            'codigo' => $encontrado ? 200 : 404, 'exito' => true,
            'mensaje' => $encontrado ? 'Consulta exitosa' : 'No se encontraron establecimientos para el RUC ingresado',
            'datos' => new ConsultaRucResource(['ruc' => $ruc, 'establecimientos' => $establecimientos]),
            'metadatos' => [
                'timestamp' => now()->toIso8601String(), 'creditos_gastados' => $costo,
                'creditos_restantes' => (int) $cobro->saldoNuevo, 'fuente' => 'catastro_sri',
            ],
        ], $encontrado ? 200 : 404);
    }

    private function respondSaldoInsuficiente(float $saldo, int $costo)
    {
        return response()->json([
            'codigo' => 402,
            'exito' => false,
            'mensaje' => 'Saldo insuficiente para realizar la consulta.',
            'error' => [
                'tipo' => 'SALDO_INSUFICIENTE',
                'detalle' => "El saldo actual es {$saldo} créditos. Se requiere al menos {$costo} crédito(s).",
            ],
            'datos' => null,
            'metadatos' => [
                'timestamp' => now()->toIso8601String(),
                'creditos_restantes' => (int) $saldo,
            ],
        ], 402);
    }

    private function respondFuenteNoDisponible(Cliente $cliente, ApiKey $apiKey, string $cedula, string $ip)
    {
        $registro = $this->cobroService->registrarSinCobro($cliente, $apiKey, 'cedula', $cedula, $ip);

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
                'creditos_restantes' => (int) $registro->saldoNuevo,
            ],
        ], 503);
    }

    private function respondSujetoNoEncontrado(Cliente $cliente, ApiKey $apiKey, string $cedula, int $costo, string $ip)
    {
        try {
            $cobro = $this->cobroService->cobrar($cliente, $apiKey, 'cedula', $cedula, $costo, null, [], $ip);
        } catch (SaldoInsuficienteException $e) {
            return $this->respondSaldoInsuficiente($e->saldoActual, $e->costo);
        }

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
                'creditos_restantes' => (int) $cobro->saldoNuevo,
            ],
        ], 404);
    }
}
