<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffWeb
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->staff || ! in_array($user->staff->rol_staff, ['admin', 'support'], true)) {
            abort(403, 'Acceso restringido a personal autorizado');
        }

        return $next($request);
    }
}
