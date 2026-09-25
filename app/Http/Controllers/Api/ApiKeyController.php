<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RotateApiKeyRequest;
use App\Models\Cliente;
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

        if ($this->ampliaPrivilegios($cliente, $options)) {
            return response()->json([
                'codigo' => 403, 'exito' => false,
                'mensaje' => 'La rotación no puede ampliar los permisos de la API Key',
                'error' => ['tipo' => 'ALCANCE_AMPLIADO_NO_PERMITIDO', 'detalle' => 'La API Key solo puede rotar hacia un alcance igual o menor al actual. Amplía los permisos desde el panel del cliente.'],
                'datos' => null,
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 403);
        }

        $key = $keys->rotate($cliente, $options, $cliente->usuario_id, $request->ip());

        return response()->json(['codigo' => 200, 'exito' => true, 'mensaje' => 'API Key rotada exitosamente',
            'datos' => ['api_key' => $key, 'prefijo' => $cliente->fresh()->api_key_prefijo],
            'metadatos' => ['timestamp' => now()->toIso8601String()]]);
    }

    /**
     * Una rotación autenticada con la propia API Key solo puede mantener sus privilegios o
     * reducirlos. Ampliarlos exige un canal que pruebe la identidad real del titular (panel web
     * o consola), no la propia Key.
     */
    private function ampliaPrivilegios(Cliente $cliente, array $options): bool
    {
        $actuales = $cliente->api_key_alcance ?? [];
        foreach ($options['scopes'] as $scope) {
            if (! ApiKeyService::cubre($actuales, $scope)) {
                return true;
            }
        }

        $ips = $cliente->api_key_ips_permitidas ?? [];
        if ($ips === []) {
            return false;
        }

        // Una lista vacía significaría "cualquier IP": soltarla también es ampliar.
        return $options['ips'] === [] || array_diff($options['ips'], $ips) !== [];
    }
}
