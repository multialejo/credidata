<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ApiKeyService;

class ValidateApiKey
{
    public function __construct(private ApiKeyService $keys) {}

    public function handle(Request $request, Closure $next, ...$permisos)
    {
        $cliente = $this->keys->authenticate($request);
        if ($cliente === null) {
            return response()->json([
                'codigo' => 401, 'exito' => false,
                'mensaje' => 'API Key requerida',
                'error' => ['tipo' => 'API_KEY_REQUERIDA', 'detalle' => null],
                'datos' => null,
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 401);
        }
        if ($cliente === 'revoked') {
            return $this->error('API_KEY_REVOCADA', 'API Key revocada', 401);
        }
        if ($cliente === 'inactive') {
            return $this->error('CLIENTE_INACTIVO', 'La cuenta del cliente está inactiva o suspendida', 403);
        }
        if ($cliente === 'invalid') {
            return response()->json([
                'codigo' => 401, 'exito' => false,
                'mensaje' => 'API Key inválida',
                'error' => ['tipo' => 'API_KEY_INVALIDA', 'detalle' => null],
                'datos' => null,
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 401);
        }

        $alcance = $cliente->api_key_alcance ?? [];
        $permitido = empty($permisos) || collect($permisos)->contains(fn (string $permiso) => in_array($permiso, $alcance, true) || in_array(strtok($permiso, ':').':*', $alcance, true)
        );

        if (! $permitido) {
            return response()->json([
                'codigo' => 403, 'exito' => false, 'mensaje' => 'Permiso insuficiente',
                'error' => ['tipo' => 'PERMISO_INSUFICIENTE', 'detalle' => null], 'datos' => null,
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 403);
        }

        $ips = $cliente->api_key_ips_permitidas ?? [];
        if ($ips !== [] && ! in_array($request->ip(), $ips, true)) {
            return response()->json([
                'codigo' => 403, 'exito' => false, 'mensaje' => 'IP no permitida',
                'error' => ['tipo' => 'IP_NO_PERMITIDA', 'detalle' => null], 'datos' => null,
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 403);
        }

        $request->merge(['cliente_autenticado' => $cliente]);

        return $next($request);
    }

    private function error(string $type, string $message, int $status)
    {
        return response()->json(['codigo' => $status, 'exito' => false, 'mensaje' => $message,
            'error' => ['tipo' => $type, 'detalle' => null], 'datos' => null,
            'metadatos' => ['timestamp' => now()->toIso8601String()]], $status);
    }
}
