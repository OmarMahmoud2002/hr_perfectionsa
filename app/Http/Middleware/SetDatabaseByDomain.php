<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetDatabaseByDomain
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $map = config('attendance.tenancy.domain_connection_map', []);
        $fallbackTenant = (string) config('attendance.tenancy.fallback_tenant', 'eg');

        $tenantConfig = $map[$host] ?? null;

        if ($tenantConfig === null) {
            if (! str_ends_with($host, '.perfectionsa.com')) {
                abort(404);
            }

            $tenantConfig = collect($map)
                ->first(fn (array $item): bool => ($item['tenant'] ?? null) === $fallbackTenant)
                ?? ['tenant' => 'eg', 'connection' => 'mysql_eg'];
        }

        $connection = (string) ($tenantConfig['connection'] ?? 'mysql_eg');
        $tenant = (string) ($tenantConfig['tenant'] ?? 'eg');

        config([
            'app.tenant' => $tenant,
            'database.default' => $connection,
        ]);

        DB::setDefaultConnection($connection);
        DB::purge($connection);
        DB::reconnect($connection);

        return $next($request);
    }
}
