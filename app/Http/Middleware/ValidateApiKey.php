<?php

namespace App\Http\Middleware;

use App\Services\ApiKeyService;
use Closure;
use Illuminate\Http\Request;

class ValidateApiKey
{
    public function __construct(private ApiKeyService $keys) {}

    public function handle(Request $request, Closure $next, ...$permisos)
    {
        $apiKey = $this->keys->authenticate($request);
        if ($apiKey === null) {
            return response()->json([
                'codigo' => 401, 'exito' => false,
                'mensaje' => 'API Key requerida',
                'error' => ['tipo' => 'API_KEY_REQUERIDA', 'detalle' => null],
                'datos' => null,
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 401);
        }
        if ($apiKey === 'revoked') {
            return $this->error('API_KEY_REVOCADA', 'API Key revocada', 401);
        }
        if ($apiKey === 'inactive') {
            return $this->error('CLIENTE_INACTIVO', 'La cuenta del cliente está inactiva o suspendida', 403);
        }
        if ($apiKey === 'invalid') {
            return response()->json([
                'codigo' => 401, 'exito' => false,
                'mensaje' => 'API Key inválida',
                'error' => ['tipo' => 'API_KEY_INVALIDA', 'detalle' => null],
                'datos' => null,
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 401);
        }

        $cliente = $apiKey->cliente;
        $alcance = $apiKey->alcance ?? [];
        $permitido = empty($permisos) || collect($permisos)->contains(fn (string $permiso) => ApiKeyService::cubre($alcance, $permiso));

        if (! $permitido) {
            return response()->json([
                'codigo' => 403, 'exito' => false, 'mensaje' => 'Permiso insuficiente',
                'error' => ['tipo' => 'PERMISO_INSUFICIENTE', 'detalle' => null], 'datos' => null,
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 403);
        }

        $ips = $apiKey->ips_permitidas ?? [];
        if ($ips !== [] && ! in_array($request->ip(), $ips, true)) {
            return response()->json([
                'codigo' => 403, 'exito' => false, 'mensaje' => 'IP no permitida',
                'error' => ['tipo' => 'IP_NO_PERMITIDA', 'detalle' => null], 'datos' => null,
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 403);
        }

        $apiKey->forceFill(['ultimo_uso_en' => now()])->saveQuietly();
        $request->merge(['cliente_autenticado' => $cliente, 'api_key_autenticada' => $apiKey]);
        // Convención: en una ruta `api.key` $request->user() es el dueño de la key y auth()->user()
        // sigue siendo null (no hay sesión). Un guard explícito nunca debe caer en ese usuario.
        $request->setUserResolver(fn (?string $guard = null) => $guard === null ? $cliente->usuario : null);

        return $next($request);
    }

    private function error(string $type, string $message, int $status)
    {
        return response()->json(['codigo' => $status, 'exito' => false, 'mensaje' => $message,
            'error' => ['tipo' => $type, 'detalle' => null], 'datos' => null,
            'metadatos' => ['timestamp' => now()->toIso8601String()]], $status);
    }
}
