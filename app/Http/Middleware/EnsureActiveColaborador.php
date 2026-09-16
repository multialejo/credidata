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
            abort(403, 'Se requiere un colaborador cliente activo.');
        }

        return $next($request);
    }
}
