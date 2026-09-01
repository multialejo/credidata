<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RotateApiKeyRequest;
use App\Services\ApiKeyService;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function revocar(Request $request)
    {
        $cliente = $request->cliente_autenticado;

        $cliente->update([
            'api_key_revocada' => true,
            'api_key_revocada_en' => now(),
        ]);

        app(ApiKeyService::class)->logRevocation($cliente, $request->user()?->id ?? $cliente->usuario_id, $request->ip());

        return response()->json([
            'codigo' => 200, 'exito' => true,
            'mensaje' => 'API Key revocada exitosamente',
            'datos' => null,
            'metadatos' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function rotar(RotateApiKeyRequest $request, ApiKeyService $keys)
    {
        $cliente = $request->cliente_autenticado;
        $input = $request->validated();
        $input['scopes'] ??= $cliente->api_key_alcance;
        $input['ips'] ??= $cliente->api_key_ips_permitidas;
        $input['alias'] ??= $cliente->api_key_alias;
        $options = $keys->validateOptions($input);
        $key = $keys->rotate($cliente, $options, $cliente->usuario_id, $request->ip());

        return response()->json(['codigo' => 200, 'exito' => true, 'mensaje' => 'API Key rotada exitosamente',
            'datos' => ['api_key' => $key, 'prefijo' => $cliente->fresh()->api_key_prefijo],
            'metadatos' => ['timestamp' => now()->toIso8601String()]]);
    }
}
