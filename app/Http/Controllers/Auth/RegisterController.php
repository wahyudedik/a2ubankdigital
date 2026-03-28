<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CustomerProfile;
use App\Models\UserOtp;
use App\Models\Account;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RegisterController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function requestOtp(Request $request): JsonResponse
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'nik' => 'required|string|size:16',
            'mother_maiden_name' => 'required|string',
            'pob' => 'required|string',
            'dob' => 'required|date',
            'gender' => 'required|in:MALE,FEMALE',
            'address_ktp' => 'required|string',
            'phone_number' => 'required|string',
            'unit_id' => 'required|exists:units,id',
            'ktp_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'selfie_image' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        // Check duplicate NIK
        $existingProfile = CustomerProfile::where('nik', $request->nik)->first();
        if ($existingProfile) {
            return response()->json([
                'status' => 'error',
                'message' => 'NIK sudah terdaftar.'
            ], 409);
        }

        DB::beginTransaction();
        try {
            // Upload files
            $nikSanitized = preg_replace("/[^a-zA-Z0-9]/", "", $request->nik);
            $ktpPath = $request->file('ktp_image')->storeAs(
                'documents',
                $nikSanitized . '_ktp_image_' . time() . '.' . $request->file('ktp_image')->extension(),
                'public'
            );
            $selfiePath = $request->file('selfie_image')->storeAs(
                'documents',
                $nikSanitized . '_selfie_image_' . time() . '.' . $request->file('selfie_image')->extension(),
                'public'
            );

            // Delete old pending verification for same email
            User::where('email', $request->email)
                ->where('status', 'PENDING_VERIFICATION')
                ->delete();

            // Create user
            $user = User::create([
                'bank_id' => date('Ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT),
                'role_id' => 9, // Customer
                'full_name' => $request->full_name,
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'status' => 'PENDING_VERIFICATION'
            ]);

            // Create customer profile
            CustomerProfile::create([
                'user_id' => $user->id,
                'unit_id' => $request->unit_id,
                'nik' => $request->nik,
                'mother_maiden_name' => $request->mother_maiden_name,
                'pob' => $request->pob,
                'dob' => $request->dob,
                'gender' => $request->gender,
                'address_ktp' => $request->address_ktp,
                'ktp_image_path' => '/storage/' . $ktpPath,
                'selfie_image_path' => '/storage/' . $selfiePath,
                'registration_method' => 'ONLINE',
                'kyc_status' => 'APPROVED'
            ]);

            // Generate OTP
            $otpCode = rand(100000, 999999);
            UserOtp::create([
                'user_id' => $user->id,
                'otp_code' => $otpCode,
                'expires_at' => now()->addMinutes(10)
            ]);

            // Send email
            $this->emailService->sendOtp($request->email, $request->full_name, $otpCode);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'OTP telah dikirim ke email Anda.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Clean up uploaded files
            if (isset($ktpPath)) Storage::disk('public')->delete($ktpPath);
            if (isset($selfiePath)) Storage::disk('public')->delete($selfiePath);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|string|size:6'
        ]);

        $user = User::where('email', $request->email)
            ->where('status', 'PENDING_VERIFICATION')
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak ditemukan atau sudah terverifikasi.'
            ], 404);
        }

        $otp = UserOtp::where('user_id', $user->id)
            ->where('otp_code', $request->otp_code)
            ->where('expires_at', '>', now())
            ->where('is_used', false)
            ->first();

        if (!$otp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode OTP tidak valid atau sudah kadaluarsa.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Mark OTP as used
            $otp->update(['is_used' => true]);

            // Activate user
            $user->update(['status' => 'ACTIVE']);

            // Create savings account
            Account::create([
                'user_id' => $user->id,
                'account_type' => 'TABUNGAN',
                'balance' => 0,
                'status' => 'ACTIVE'
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Verifikasi berhasil. Akun Anda sudah aktif.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memverifikasi OTP: ' . $e->getMessage()
            ], 500);
        }
    }

    public function forgotPasswordRequest(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email tidak terdaftar.'
            ], 404);
        }

        // Generate OTP
        $otpCode = rand(100000, 999999);
        UserOtp::create([
            'user_id' => $user->id,
            'otp_code' => $otpCode,
            'expires_at' => now()->addMinutes(10)
        ]);

        // Send email
        $this->emailService->sendPasswordReset($request->email, $user->full_name, $otpCode);

        return response()->json([
            'status' => 'success',
            'message' => 'Kode reset password telah dikirim ke email Anda.'
        ]);
    }

    public function forgotPasswordReset(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|string|size:6',
            'new_password' => 'required|string|min:6'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email tidak terdaftar.'
            ], 404);
        }

        $otp = UserOtp::where('user_id', $user->id)
            ->where('otp_code', $request->otp_code)
            ->where('expires_at', '>', now())
            ->where('is_used', false)
            ->first();

        if (!$otp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode OTP tidak valid atau sudah kadaluarsa.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Mark OTP as used
            $otp->update(['is_used' => true]);

            // Update password
            $user->update([
                'password_hash' => Hash::make($request->new_password)
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Password berhasil direset.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mereset password: ' . $e->getMessage()
            ], 500);
        }
    }
}
