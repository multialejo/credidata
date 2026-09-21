<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DecisionAporteRequest;
use App\Http\Resources\AporteResource;
use App\Models\Aporte;
use App\Services\ColaboracionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAporteController extends Controller
{
    public function pendientes(Request $request): JsonResponse
    {
        $aportes = Aporte::with('colaborador.usuario')->where('estado', 'pendiente')->latest('fecha')->paginate(15);

        return response()->json(['codigo' => 200, 'exito' => true, 'mensaje' => 'Aportes pendientes', 'datos' => ['aportes' => AporteResource::collection($aportes->items()), 'paginacion' => ['total' => $aportes->total(), 'per_page' => $aportes->perPage(), 'current_page' => $aportes->currentPage(), 'last_page' => $aportes->lastPage()]]]);
    }

    public function aprobar(DecisionAporteRequest $request, Aporte $aporte, ColaboracionService $service): JsonResponse
    {
        return $this->decidir($request, $aporte, $service, true);
    }

    public function rechazar(DecisionAporteRequest $request, Aporte $aporte, ColaboracionService $service): JsonResponse
    {
        return $this->decidir($request, $aporte, $service, false);
    }

    private function decidir(DecisionAporteRequest $request, Aporte $aporte, ColaboracionService $service, bool $aprobar): JsonResponse
    {
        $aporte = $service->decidir($aporte, $request->user(), $aprobar, $request->validated('comentario'), $request->ip());

        return response()->json(['codigo' => 200, 'exito' => true, 'mensaje' => $aporte->estado === 'aprobado' ? 'Aporte aprobado' : 'Aporte rechazado', 'datos' => ['aporte' => new AporteResource($aporte)]]);
    }
}
