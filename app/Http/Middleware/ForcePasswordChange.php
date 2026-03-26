<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        $allowedRoutes = [
            'password.force-change',
            'password.force-change.update',
            'logout',
        ];

        if ($request->route()?->getName() && in_array($request->route()->getName(), $allowedRoutes, true)) {
            return $next($request);
        }

        return redirect()
            ->route('password.force-change')
            ->with('warning', 'يجب تغيير كلمة المرور قبل متابعة استخدام النظام.');
    }
}
