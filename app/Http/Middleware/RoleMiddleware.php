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
     * @param  string  $roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $roles)
    {
        // Pisahkan role berdasarkan '|' lalu lowercase semuanya
        $allowedRoles = array_map('strtolower', explode('|', $roles));

        // Cek apakah user sudah login dan apakah rolenya sesuai dengan yang diizinkan
        if (!$request->user() || !in_array(strtolower($request->user()->role), $allowedRoles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses (Akses khusus ' . implode(', ', $allowedRoles) . ')'
            ], 403);
        }

        return $next($request);
    }
}