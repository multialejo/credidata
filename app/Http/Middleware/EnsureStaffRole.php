<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffRole
{
    /**
     * Requiere que el usuario autenticado tenga fila Staff con el rol indicado
     * (ej. 'admin'). El middleware base `staff` ya garantiza la existencia de
     * la fila; este middleware solo verifica el rol.
     */
    public function handle(Request $request, Closure $next, string $rol): Response
    {
        $user = $request->user();

        if (! $user || ! $user->staff || $user->staff->rol_staff !== $rol) {
            return response()->json([
                'codigo' => 403,
                'exito' => false,
                'mensaje' => 'Acción restringida al rol de personal autorizado',
                'error' => ['tipo' => 'ROL_NO_AUTORIZADO', 'detalle' => null],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 403);
        }

        return $next($request);
    }
}