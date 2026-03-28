<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            if ($request->expectsJson() || $request->is('ajax/*')) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
            }
            return redirect('/login');
        }

        $userRoleId = $request->user()->role_id;

        // Convert role names to IDs (must match roles table)
        $roleMap = [
            'super_admin' => 1,
            'admin' => 2,          // Kepala Cabang
            'manager' => 3,        // Kepala Unit
            'marketing' => 4,      // Marketing
            'teller' => 5,         // Teller
            'cs' => 6,             // Customer Service
            'analyst' => 7,        // Analis Kredit
            'debt_collector' => 8, // Debt Collector
            'customer' => 9,       // Nasabah
        ];

        $allowedRoleIds = array_filter(array_map(
            fn($role) => $roleMap[trim($role)] ?? null,
            $roles
        ));

        if (!in_array($userRoleId, $allowedRoleIds)) {
            if ($request->expectsJson() || $request->is('ajax/*')) {
                return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
            }

            // Redirect based on role
            if ($userRoleId === 9) {
                return redirect('/dashboard');
            }
            return redirect('/admin/dashboard');
        }

        return $next($request);
    }
}
