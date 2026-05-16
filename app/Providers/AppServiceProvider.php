<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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
            return Limit::perMinute(1)->by($request->user()?->id ?: $request->ip());
        });
    }

    public static function checkPaymentRateLimit(Request $request): ?\Illuminate\Http\JsonResponse
    {
        $key = 'payment:' . ($request->user()?->id ?: $request->ip());
        
        if (RateLimiter::tooManyAttempts($key, 1)) {
            $retryAfter = RateLimiter::availableIn($key);
            return response()->json([
                'error' => 'Umefikia kikomo cha malipo. Subiri dakika 30 kabla ya kujaribu tena.',
                'retry_after' => $retryAfter
            ], 429);
        }
        
        RateLimiter::hit($key, 1800); // 30 minutes
        return null;
    }
}
