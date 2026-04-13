<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureMiddleware
{
    public function handle(Request $request, Closure $next, string $key): Response
    {
        if (! feature($key)) {
            abort(404);
        }

        return $next($request);
    }
}
