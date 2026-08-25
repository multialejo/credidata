<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCliente
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->staff) {
            return redirect()->route('admin.clientes');
        }

        if (! $user->cliente) {
            abort(403);
        }

        return $next($request);
    }
}
