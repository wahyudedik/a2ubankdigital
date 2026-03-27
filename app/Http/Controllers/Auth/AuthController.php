<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserOtp;
use App\Models\CustomerProfile;
use App\Models\PasswordReset;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user
     * POST /api/auth/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            if ($user) {
                $user->incrementFailedLogins();
            }
            
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
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

        // Reset failed login attempts
        $user->resetFailedLogins();
        $user->updateLastLogin($request->ip());

        // Create token
        $token = $user->createToken('auth-token', ['*'], now()->addDay())->plainTextToken;

        // Log login
        $user->loginHistory()->create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'bankId' => $user->bank_id,
                'roleId' => $user->role_id,
                'fullName' => $user->full_name,
                'email' => $user->email,
            ]
        ]);
    }

    /**
     * Request OTP for registration
     * POST /api/auth/register/request-otp
     */
    public function requestOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|unique:users,phone_number',
            'full_name' => 'required|string|max:255',
        ]);

        // Generate OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(5);

        // Store OTP temporarily (you might want to use cache instead)
        DB::table('user_otps')->updateOrInsert(
            ['email' => $request->email],
            [
                'otp_hash' => Hash::make($otp),
                'expires_at' => $expiresAt,
                'created_at' => now(),
            ]
        );

        // Send OTP via email
        try {
            Mail::raw("Kode OTP Anda: {$otp}\n\nKode ini berlaku selama 5 menit.", function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Kode OTP Registrasi - A2U Bank Digital');
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim OTP. Silakan coba lagi.'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Kode OTP telah dikirim ke email Anda.',
            'data' => [
                'email' => $request->email,
                'expires_in' => 300, // 5 minutes in seconds
            ]
        ]);
    }

    /**
     * Verify OTP and complete registration
     * POST /api/auth/register/verify-otp
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8',
            'nik' => 'required|string|size:16|unique:customer_profiles,nik',
            'mother_maiden_name' => 'required|string',
            'pob' => 'required|string',
            'dob' => 'required|date',
            'gender' => 'required|in:L,P',
            'address_ktp' => 'required|string',
        ]);

        // Verify OTP
        $otpRecord = DB::table('user_otps')
            ->where('email', $request->email)
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'status' => 'error',
                'message' => 'OTP tidak ditemukan atau sudah kadaluarsa.'
            ], 400);
        }

        if (now()->gt($otpRecord->expires_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'OTP sudah kadaluarsa. Silakan request OTP baru.'
            ], 400);
        }

        if (!Hash::check($request->otp, $otpRecord->otp_hash)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode OTP salah.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Create user
            $user = User::create([
                'bank_id' => 'BNK' . str_pad(User::max('id') + 1, 6, '0', STR_PAD_LEFT),
                'role_id' => Role::CUSTOMER,
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'password_hash' => Hash::make($request->password),
                'status' => 'ACTIVE',
            ]);

            // Create customer profile
            CustomerProfile::create([
                'user_id' => $user->id,
                'nik' => $request->nik,
                'mother_maiden_name' => $request->mother_maiden_name,
                'pob' => $request->pob,
                'dob' => $request->dob,
                'gender' => $request->gender,
                'address_ktp' => $request->address_ktp,
                'kyc_status' => 'PENDING',
            ]);

            // Create default savings account
            $user->accounts()->create([
                'account_type' => 'TABUNGAN',
                'balance' => 0,
                'status' => 'ACTIVE',
            ]);

            // Delete used OTP
            DB::table('user_otps')->where('email', $request->email)->delete();

            DB::commit();

            // Create token
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Registrasi berhasil. Selamat datang!',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'bankId' => $user->bank_id,
                    'roleId' => $user->role_id,
                    'fullName' => $user->full_name,
                    'email' => $user->email,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Registrasi gagal. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Request password reset
     * POST /api/auth/forgot-password
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Generate reset token
        $token = Str::random(64);
        $expiresAt = now()->addHour();

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'expires_at' => $expiresAt,
                'created_at' => now(),
            ]
        );

        // Send reset link via email
        $resetLink = config('app.frontend_url') . "/reset-password?token={$token}&email={$request->email}";

        try {
            Mail::raw("Klik link berikut untuk reset password Anda:\n\n{$resetLink}\n\nLink ini berlaku selama 1 jam.", function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Reset Password - A2U Bank Digital');
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim email. Silakan coba lagi.'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Link reset password telah dikirim ke email Anda.'
        ]);
    }

    /**
     * Reset password
     * POST /api/auth/reset-password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $resetRecord = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token reset tidak valid.'
            ], 400);
        }

        if (now()->gt($resetRecord->expires_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token reset sudah kadaluarsa.'
            ], 400);
        }

        if (!Hash::check($request->token, $resetRecord->token)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token reset tidak valid.'
            ], 400);
        }

        // Update password
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password_hash' => Hash::make($request->new_password),
        ]);

        // Delete used token
        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil direset. Silakan login dengan password baru Anda.'
        ]);
    }

    /**
     * Logout user
     * POST /api/auth/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil.'
        ]);
    }
    /**
     * Approve new device for user account
     */
    public function approveNewDevice(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'device_token' => 'required|string',
                'device_name' => 'required|string|max:100',
                'approval_code' => 'required|string|size:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();

            // Verify approval code (should be sent via SMS/email)
            $storedCode = Cache::get("device_approval_{$user->id}_{$request->device_token}");

            if (!$storedCode || $storedCode !== $request->approval_code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired approval code'
                ], 400);
            }

            // Check if device already exists
            $existingDevice = DB::table('user_devices')
                ->where('user_id', $user->id)
                ->where('device_token', $request->device_token)
                ->first();

            if ($existingDevice) {
                // Update existing device
                DB::table('user_devices')
                    ->where('id', $existingDevice->id)
                    ->update([
                        'device_name' => $request->device_name,
                        'is_approved' => true,
                        'approved_at' => now(),
                        'updated_at' => now()
                    ]);
            } else {
                // Create new approved device
                DB::table('user_devices')->insert([
                    'user_id' => $user->id,
                    'device_token' => $request->device_token,
                    'device_name' => $request->device_name,
                    'device_type' => $request->input('device_type', 'mobile'),
                    'is_approved' => true,
                    'approved_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Clear the approval code
            Cache::forget("device_approval_{$user->id}_{$request->device_token}");

            return response()->json([
                'success' => true,
                'message' => 'Device approved successfully',
                'data' => [
                    'device_name' => $request->device_name,
                    'approved_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve device'
            ], 500);
        }
    }

    /**
     * Request device approval (send approval code)
     */
    public function requestDeviceApproval(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'device_token' => 'required|string',
                'device_name' => 'required|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();

            // Generate 6-digit approval code
            $approvalCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store approval code for 10 minutes
            Cache::put(
                "device_approval_{$user->id}_{$request->device_token}",
                $approvalCode,
                600 // 10 minutes
            );

            return response()->json([
                'success' => true,
                'message' => 'Approval code sent to your registered contact methods',
                'data' => [
                    'expires_in' => 600 // 10 minutes
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send approval code'
            ], 500);
        }
    }
}
