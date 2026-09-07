<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSwaggerAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            config('l5-swagger.public') || app()->environment(['local', 'testing']),
            404,
        );

        return $next($request);
    }
}
