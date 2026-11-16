<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        $this->rateLimits();
    }

    /**
     * The limits for the API.
     *
     * A token is counted per token, not per address, so one busy integration
     * cannot lock out the other people behind the same network.
     */
    private function rateLimits(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $token = $request->user()?->currentAccessToken()?->id;

            return [
                Limit::perMinute(60)->by('token:'.($token ?? $request->ip())),
                Limit::perDay(1000)->by('token:'.($token ?? $request->ip())),
            ];
        });
    }
}
