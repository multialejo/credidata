<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\Consulta;
use App\Models\LogActividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ConsultaController extends Controller
{
    public function consultaCedula(Request $request)
    {
        $request->validate([
            'cedula' => ['required', 'string', 'digits_between:8,13'],
        ]);

        $bearer = $request->header('Authorization');
        if (!$bearer || !str_starts_with($bearer, 'Bearer ')) {
            return response()->json(['error' => 'API_KEY_REQUERIDA'], 401);
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
            return response()->json(['error' => 'API_KEY_INVALIDA'], 401);
        }

        if ($cliente->saldo_creditos <= 0) {
            return response()->json(['error' => 'SALDO_INSUFICIENTE'], 402);
        }

        $param = ConfigParametro::where('clave', 'costoConsultaBase')->first();
        $costo = $param ? (int) json_decode($param->valor) : 1;

        $cliente->timestamps = false;
        $cliente->updateQuietly(['api_key_ultimo_uso' => now()]);
        $cliente->decrement('saldo_creditos', $costo);
        $cliente->refresh();

        $consulta = Consulta::create([
            'cliente_id' => $cliente->uid,
            'tipo' => 'cedula',
            'identificador' => $request->cedula,
            'creditos_gastados' => $costo,
            'exitosa' => true,
            'ip_origen' => $request->ip(),
            'origen' => 'api',
        ]);

        LogActividad::create([
            'accion' => 'CONSULTA_CEDULA',
            'actor_id' => $cliente->uid,
            'detalle' => [
                'consulta_id' => $consulta->id,
                'cedula' => $request->cedula,
                'creditos_gastados' => $costo,
            ],
            'ip_origen' => $request->ip(),
        ]);

        return response()->json([
            'consulta_id' => $consulta->id,
            'cedula' => $request->cedula,
            'creditos_gastados' => (int) $costo,
            'creditos_restantes' => (int) $cliente->saldo_creditos,
            'mensaje' => 'consulta exitosa',
        ]);
    }
}
