<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffRole
{
    /**
     * Requiere que el usuario autenticado tenga fila Staff con uno de los roles
     * indicados. El middleware base `staff` ya garantiza el acceso de staff.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->staff || ! in_array($user->staff->rol_staff, $roles, true)) {
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
