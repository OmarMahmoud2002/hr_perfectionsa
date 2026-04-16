<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        $previousDefault = (string) config('database.default', 'mysql');

        $tenantConfig = $map[$host] ?? null;

        if ($tenantConfig === null) {
            $isAllowedProductionHost = str_ends_with($host, '.perfectionsa.com');
            $isAllowedLocalHost = str_ends_with($host, '.localhost');

            // In local, require explicit mapped subdomains to prevent accidental fallback writes.
            if ($isAllowedLocalHost) {
                abort(404);
            }

            if (! $isAllowedProductionHost && ! $isAllowedLocalHost) {
                abort(404);
            }

            $tenantConfig = collect($map)
                ->first(fn (array $item): bool => ($item['tenant'] ?? null) === $fallbackTenant)
                ?? ['tenant' => 'eg', 'connection' => 'mysql_eg'];
        }

        $connection = (string) ($tenantConfig['connection'] ?? 'mysql_eg');
        $tenant = (string) ($tenantConfig['tenant'] ?? 'eg');
        $sessionCookieName = Str::slug((string) config('app.name', 'laravel'), '_') . '_' . $tenant . '_session';

        config([
            'app.tenant' => $tenant,
            'database.default' => $connection,
            // Keep each tenant/domain on its own session cookie to avoid cross-subdomain token mismatch.
            'session.cookie' => $sessionCookieName,
            'session.domain' => $host,
        ]);

        DB::setDefaultConnection($connection);

        if ($previousDefault !== '' && $previousDefault !== $connection) {
            DB::purge($previousDefault);
        }

        DB::purge('mysql_eg');
        DB::purge('mysql_sa');
        DB::purge($connection);
        DB::reconnect($connection);

        return $next($request);
    }
}
