<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    protected $logService;

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            if ($user) {
                $user->increment('failed_login_attempts');
                $this->logService->logLogin($user->id, false, 'Invalid password');
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password salah.'
            ], 401);
        }

        if ($user->status === 'BLOCKED') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda diblokir. Silakan hubungi Customer Service.'
            ], 403);
        }

        if ($user->status === 'PENDING_VERIFICATION') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum aktif. Silakan cek email Anda untuk verifikasi OTP.'
            ], 403);
        }

        if ($user->status !== 'ACTIVE') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda tidak aktif. Hubungi Customer Service untuk informasi lebih lanjut.'
            ], 403);
        }

        // Reset failed attempts
        $user->update(['failed_login_attempts' => 0]);

        // Login via session (monolith - no token needed)
        Auth::login($user);

        // Regenerate session for security
        $request->session()->regenerate();

        // Log successful login
        $this->logService->logLogin($user->id, true);

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'user' => [
                'id' => $user->id,
                'bankId' => $user->bank_id,
                'roleId' => $user->role_id,
                'fullName' => $user->full_name,
                'email' => $user->email
            ]
        ]);
    }

    public function logout(): JsonResponse
    {
        $userId = Auth::id();
        $this->logService->logAudit('LOGOUT', 'users', $userId);

        Auth::logout();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil.'
        ]);
    }
}
