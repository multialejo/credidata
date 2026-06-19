<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\LogActividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiKeyController extends Controller
{
    public function revocar(Request $request)
    {
        $bearer = $request->header('Authorization');
        if (!$bearer || !str_starts_with($bearer, 'Bearer ')) {
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

        if (!$cliente) {
            return response()->json([
                'codigo' => 401, 'exito' => false,
                'mensaje' => 'API Key inválida',
                'error' => ['tipo' => 'API_KEY_INVALIDA', 'detalle' => null],
                'datos' => null,
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 401);
        }

        $cliente->update([
            'api_key_revocada' => true,
            'api_key_revocada_en' => now(),
        ]);

        LogActividad::create([
            'accion' => 'API_KEY_REVOCADA',
            'actor_id' => $cliente->uid,
            'detalle' => ['prefijo' => $cliente->api_key_prefijo],
            'ip_origen' => $request->ip(),
        ]);

        return response()->json([
            'codigo' => 200, 'exito' => true,
            'mensaje' => 'API Key revocada exitosamente',
            'datos' => null,
            'metadatos' => ['timestamp' => now()->toIso8601String()],
        ]);
    }
}
