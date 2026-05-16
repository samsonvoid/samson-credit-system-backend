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

        RateLimiter::for('payment-initiate', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('payment-confirm', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }

    public static function checkPaymentRateLimit(Request $request, string $type = 'initiate'): ?\Illuminate\Http\JsonResponse
    {
        $limiterKey = 'payment:' . $type . ':' . ($request->user()?->id ?: $request->ip());
        
        if (RateLimiter::tooManyAttempts($limiterKey, $type === 'initiate' ? 5 : 10)) {
            $retryAfter = RateLimiter::availableIn($limiterKey);
            return response()->json([
                'error' => 'Umefanya majaribio mengi sana. Subiri sekunde ' . $retryAfter . ' kabla ya kujaribu tena.',
                'retry_after' => $retryAfter
            ], 429)->withHeaders([
                'Retry-After' => $retryAfter,
                'Content-Type' => 'application/json'
            ]);
        }
        
        RateLimiter::hit($limiterKey, 60); // Decay 60 seconds
        return null;
    }
}
