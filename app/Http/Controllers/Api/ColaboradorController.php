<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ActivarColaboradorRequest;
use App\Http\Requests\Api\RegistrarAporteRequest;
use App\Http\Resources\AporteResource;
use App\Models\Aporte;
use App\Models\Colaborador;
use App\Services\ColaboracionService;
use Illuminate\Http\JsonResponse;

class ColaboradorController extends Controller
{
    public function registro(ActivarColaboradorRequest $request, ColaboracionService $service): JsonResponse
    {
        $existing = Colaborador::where('usuario_id', $request->user()->id)->exists();
        $colaborador = $service->activar($request->user(), $request->ip());
        return response()->json(['codigo' => $existing ? 200 : 201, 'exito' => true, 'mensaje' => 'Colaborador activo', 'datos' => ['colaborador' => ['id' => $colaborador->id, 'estado' => $colaborador->estado_colaborador, 'terminos_version' => $colaborador->terminos_version, 'terminos_aceptados_en' => $colaborador->terminos_aceptados_en?->toIso8601String()]]], $existing ? 200 : 201);
    }

    public function datos(RegistrarAporteRequest $request, ColaboracionService $service): JsonResponse
    {
        $aporte = $service->registrarAporte($request->user()->colaborador, ...[$request->validated('identificador'), $request->validated('tipo_dato'), $request->validated('valor'), $request->ip()]);
        return response()->json(['codigo' => 201, 'exito' => true, 'mensaje' => 'Aporte registrado', 'datos' => ['aporte' => new AporteResource($aporte)]], 201);
    }

    public function show(Aporte $aporte): JsonResponse
    {
        abort_unless($aporte->colaborador_id === request()->user()->colaborador->id, 403);
        return response()->json(['codigo' => 200, 'exito' => true, 'mensaje' => 'Aporte', 'datos' => ['aporte' => new AporteResource($aporte)]]);
    }
}
