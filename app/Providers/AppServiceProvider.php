<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

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
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('payment-limit', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();
            $remaining = RateLimiter::remaining('payment:' . $key, 1, 1800);
            if ($remaining < 1) {
                $retryAfter = RateLimiter::availableIn('payment:' . $key);
                throw new TooManyRequestsHttpException($retryAfter, 'Umefikia kikomo cha malipo. Subiri dakika 30 kabla ya kujaribu tena.');
            }
            return Limit::perMinute(2)->by($key);
        });
    }
}
