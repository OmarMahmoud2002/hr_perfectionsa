<?php

namespace App\Providers;

use App\Services\Feature\FeatureService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::if('feature', fn (string $key): bool => app(FeatureService::class)->enabled($key));
    }
}
