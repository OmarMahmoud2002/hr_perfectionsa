<?php

if (! function_exists('tenant')) {
    function tenant(): string
    {
        $configuredTenant = config('app.tenant');

        if (is_string($configuredTenant) && $configuredTenant !== '') {
            return $configuredTenant;
        }

        if (app()->runningInConsole()) {
            return 'eg';
        }

        $host = request()->getHost();

        return match ($host) {
            'hrsa.perfectionsa.com' => 'sa',
            default => 'eg',
        };
    }
}
