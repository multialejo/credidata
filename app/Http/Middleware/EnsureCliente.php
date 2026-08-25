<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCliente
{
    /**
     * Autoriza rutas web del dashboard del cliente.
     * Si el usuario autenticado no tiene fila Cliente:
     *  - Si tiene fila Staff, redirige a /admin/clientes (UX coherente).
     *  - Si no, abort 403 (usuario huérfano, no debería existir).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->cliente) {
            if ($user && $user->staff) {
                return redirect()->route('admin.clientes');
            }

            abort(403, 'Acceso restringido a clientes');
        }

        return $next($request);
    }
}
