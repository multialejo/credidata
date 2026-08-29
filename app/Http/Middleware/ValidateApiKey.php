<?php

namespace App\Http\Middleware;

use App\Models\Cliente;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ValidateApiKey
{
    public function handle(Request $request, Closure $next, ...$permisos)
    {
        $bearer = $request->header('Authorization');
        if (! $bearer || ! str_starts_with($bearer, 'Bearer ')) {
            return response()->json([
                'codigo' => 401, 'exito' => false,
                'mensaje' => 'API Key requerida',
                'error' => ['tipo' => 'API_KEY_REQUERIDA', 'detalle' => null],
                'datos' => null,
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 401);
        }
        $apiKey = substr($bearer, 7);

        $cliente = null;
        foreach (Cliente::where('api_key_revocada', false)->whereNotNull('api_key_hash')->cursor() as $c) {
            if (Hash::check($apiKey, $c->api_key_hash)) {
                $cliente = $c;
                break;
            }
        }

        if (! $cliente) {
            return response()->json([
                'codigo' => 401, 'exito' => false,
                'mensaje' => 'API Key inválida o revocada',
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
}
