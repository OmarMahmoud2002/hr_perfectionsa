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
            'hrsa.perfectionsa.com',
            'hrsa.localhost' => 'sa',
            default => 'eg',
        };
    }
}

if (! function_exists('feature')) {
    function feature(string $key, ?bool $default = null): bool
    {
        return app(\App\Services\Feature\FeatureService::class)->enabled($key, $default);
    }
}

