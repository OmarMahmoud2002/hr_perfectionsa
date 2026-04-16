<?php

namespace App\Http\Middleware;

use App\Services\Feature\FeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureMiddleware
{
    public function handle(Request $request, Closure $next, string $key): Response
    {
        if (! app(FeatureService::class)->enabled($key)) {
            abort(404);
        }

        return $next($request);
    }
}
