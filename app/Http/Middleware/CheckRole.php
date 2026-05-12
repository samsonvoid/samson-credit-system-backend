<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        
        if (!$user) {
            abort(403, 'Unauthorized');
        }
        
        if ($role === 'customer') {
            if (get_class($user) === 'App\\Models\\Customer') {
                return $next($request);
            }
            abort(403, 'Unauthorized. Customer access only.');
        }
        
        if ($user->role !== $role) {
            abort(403, 'Unauthorized. This action requires ' . $role . ' privileges.');
        }

        return $next($request);
    }
}
