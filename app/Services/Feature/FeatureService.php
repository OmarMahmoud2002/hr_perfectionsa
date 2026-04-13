<?php

namespace App\Services\Feature;

use App\Models\Feature;
use InvalidArgumentException;
use Illuminate\Support\Facades\Cache;

class FeatureService
{
    public function enabled(string $key, ?bool $default = null): bool
    {
        $cacheKey = $this->cacheKey($key);
        $ttl = (int) config('features.cache_ttl', 300);

        return Cache::remember($cacheKey, $ttl, function () use ($key, $default): bool {
            $registry = (array) config('features.registry', []);
            $existsInRegistry = array_key_exists($key, $registry);

            if (! $existsInRegistry && $this->isStrictMode()) {
                throw new InvalidArgumentException("Unknown feature key [{$key}].");
            }

            $configDefault = $existsInRegistry
                ? (bool) (($registry[$key]['default'] ?? false))
                : null;

            $fallback = $default ?? $configDefault ?? false;

            $feature = Feature::query()->where('key', $key)->first();

            return $feature ? (bool) $feature->enabled : (bool) $fallback;
        });
    }

    public function clear(string $key): void
    {
        Cache::forget($this->cacheKey($key));
    }

    private function cacheKey(string $key): string
    {
        return tenant() . '_feature_' . $key;
    }

    private function isStrictMode(): bool
    {
        return (bool) config('features.strict', false)
            && app()->environment(['local', 'testing']);
    }
}
