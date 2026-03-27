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
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.'
            ], 401);
        }

        $userRoleId = $request->user()->role_id;

        // Convert role names to IDs (must match roles table)
        $roleMap = [
            'super_admin' => 1,
            'admin' => 2,       // Kepala Cabang
            'manager' => 3,     // Kepala Unit
            'marketing' => 4,   // Marketing
            'teller' => 5,      // Teller
            'cs' => 6,          // Customer Service
            'analyst' => 7,     // Analis Kredit
            'debt_collector' => 8, // Debt Collector
            'customer' => 9     // Nasabah
        ];

        $allowedRoleIds = array_map(function($role) use ($roleMap) {
            return $roleMap[$role] ?? null;
        }, $roles);

        if (!in_array($userRoleId, $allowedRoleIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses resource ini.'
            ], 403);
        }

        return $next($request);
    }
}
