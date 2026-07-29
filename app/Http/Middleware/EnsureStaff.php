<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->staff) {
            return response()->json([
                'codigo' => 403,
                'exito' => false,
                'mensaje' => 'Acceso restringido a personal autorizado',
                'error' => ['tipo' => 'NO_STAFF', 'detalle' => null],
                'metadatos' => ['timestamp' => now()->toIso8601String()],
            ], 403);
        }

        return $next($request);
    }
}
