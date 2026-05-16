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
        
        RateLimiter::hit($limiterKey, 60);
        return null;
    }

    public static function check30MinCooldown(Request $request): ?\Illuminate\Http\JsonResponse
    {
        $userId = $request->user()?->id;
        if (!$userId) return null;
        
        $key = 'payment_cooldown:' . $userId;
        $lastConfirmed = \Illuminate\Support\Facades\Cache::get($key);
        
        if ($lastConfirmed) {
            $secondsPassed = now()->diffInSeconds($lastConfirmed);
            $secondsRemaining = max(0, 1800 - $secondsPassed); // 30 minutes
            
            if ($secondsRemaining > 0) {
                return response()->json([
                    'error' => 'Umeshatuma ombi la malipo. Subiri dakika ' . ceil($secondsRemaining / 60) . ' kabla ya kutuma ombi jingine.',
                    'cooldown_remaining' => $secondsRemaining,
                    'can_retry_at' => now()->addSeconds($secondsRemaining)->toIso8601String()
                ], 429)->withHeaders([
                    'Retry-After' => $secondsRemaining,
                    'Content-Type' => 'application/json'
                ]);
            }
        }
        
        return null;
    }

    public static function set30MinCooldown(Request $request): void
    {
        $userId = $request->user()?->id;
        if ($userId) {
            $key = 'payment_cooldown:' . $userId;
            \Illuminate\Support\Facades\Cache::put($key, now(), 1800); // 30 minutes
        }
    }
}
