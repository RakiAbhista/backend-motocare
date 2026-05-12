<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Cek apakah user sudah login dan apakah rolenya sesuai dengan yang diizinkan
        // Gunakan strtolower() untuk case-insensitive comparison
        if (!$request->user() || !in_array(strtolower($request->user()->role), $roles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses (Akses khusus ' . implode(', ', $roles) . ')'
            ], 403);
        }

        return $next($request);
    }
}