<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // Force HTTPS scheme in production, but only for web requests (not CLI/artisan)
        if (! $this->app->runningInConsole() && ($this->app->environment('production') || config('app.env') === 'production')) {
            URL::forceScheme('https');
        }
    }
}
