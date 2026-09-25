<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveColaborador
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $colaborador = $user?->colaborador;

        if (! $user?->cliente || ! $colaborador || $colaborador->estado_colaborador !== 'activo') {
            return response()->json([
                'codigo' => 403, 'exito' => false,
                'mensaje' => 'Se requiere un colaborador cliente activo.',
                'error' => ['tipo' => 'COLABORADOR_NO_ACTIVO', 'detalle' => null],
                'datos' => null,
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 403);
        }

        return $next($request);
    }
}
