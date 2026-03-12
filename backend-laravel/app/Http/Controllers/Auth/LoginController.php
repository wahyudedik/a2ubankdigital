<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            if ($user) {
                $user->increment('failed_login_attempts');
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password salah.'
            ], 401);
        }

        // Check account status
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

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'token' => $token,
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
        Auth::user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil.'
        ]);
    }
}
