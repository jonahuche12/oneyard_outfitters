<?php

namespace App\Providers;

use App\Auth\ActivePasswordResetUserProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
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
        Auth::provider('active-eloquent', function ($app, array $config) {
            return new ActivePasswordResetUserProvider(
                $app['hash']->driver(),
                $config['model']
            );
        });

        RateLimiter::for('mail', function () {
            return Limit::perMinute(30);
        });
    }
}
