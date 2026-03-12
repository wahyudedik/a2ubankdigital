<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSession;
use App\Models\UserOtp;
use App\Services\EmailService;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SecurityController extends Controller
{
    protected $emailService;
    protected $logService;

    public function __construct(EmailService $emailService, LogService $logService)
    {
        $this->emailService = $emailService;
        $this->logService = $logService;
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed'
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password lama tidak sesuai.'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        // Log password change
        $this->logService->logAudit('PASSWORD_CHANGED', 'users', $user->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diperbarui.'
        ]);
    }

    /**
     * Update PIN
     */
    public function updatePin(Request $request): JsonResponse
    {
        $request->validate([
            'current_pin' => 'sometimes|string|size:6',
            'new_pin' => 'required|string|size:6|confirmed'
        ]);

        $user = Auth::user();

        // Check current PIN if user already has one
        if ($user->transaction_pin && !Hash::check($request->current_pin, $user->transaction_pin)) {
            return response()->json([
                'status' => 'error',
                'message' => 'PIN lama tidak sesuai.'
            ], 400);
        }

        $user->update([
            'transaction_pin' => Hash::make($request->new_pin)
        ]);

        // Log PIN change
        $this->logService->logAudit('PIN_CHANGED', 'users', $user->id);

        return response()->json([
            'status' => 'success',
            'message' => 'PIN transaksi berhasil diperbarui.'
        ]);
    }

    /**
     * Setup 2FA
     */
    public function setup2fa(Request $request): JsonResponse
    {
        $request->validate([
            'enable' => 'required|boolean'
        ]);

        $user = Auth::user();

        if ($request->enable) {
            // Generate OTP for 2FA setup
            $otpCode = rand(100000, 999999);
            
            UserOtp::create([
                'user_id' => $user->id,
                'otp_code' => $otpCode,
                'expires_at' => now()->addMinutes(10),
                'purpose' => '2FA_SETUP'
            ]);

            // Send OTP via email
            $this->emailService->send(
                $user->email,
                $user->full_name,
                'Setup 2FA - Kode Verifikasi',
                'otp',
                [
                    'full_name' => $user->full_name,
                    'otp_code' => $otpCode,
                    'preheader' => 'Kode verifikasi untuk mengaktifkan 2FA.'
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Kode verifikasi telah dikirim ke email Anda.',
                'requires_verification' => true
            ]);
        } else {
            // Disable 2FA
            $user->update(['two_factor_enabled' => false]);

            $this->logService->logAudit('2FA_DISABLED', 'users', $user->id);

            return response()->json([
                'status' => 'success',
                'message' => '2FA berhasil dinonaktifkan.'
            ]);
        }
    }

    /**
     * Verify 2FA setup
     */
    public function verify2fa(Request $request): JsonResponse
    {
        $request->validate([
            'otp_code' => 'required|string|size:6'
        ]);

        $user = Auth::user();

        $otp = UserOtp::where('user_id', $user->id)
            ->where('otp_code', $request->otp_code)
            ->where('purpose', '2FA_SETUP')
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

            // Enable 2FA
            $user->update(['two_factor_enabled' => true]);

            // Log 2FA enabled
            $this->logService->logAudit('2FA_ENABLED', 'users', $user->id);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => '2FA berhasil diaktifkan.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengaktifkan 2FA.'
            ], 500);
        }
    }

    /**
     * Get active sessions
     */
    public function getActiveSessions(): JsonResponse
    {
        $sessions = UserSession::where('user_id', Auth::id())
            ->where('expires_at', '>', now())
            ->orderBy('last_activity', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $sessions
        ]);
    }

    /**
     * Terminate session
     */
    public function terminateSession(Request $request, $sessionId): JsonResponse
    {
        $session = UserSession::where('user_id', Auth::id())
            ->where('id', $sessionId)
            ->first();

        if (!$session) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi tidak ditemukan.'
            ], 404);
        }

        $session->delete();

        // Log session termination
        $this->logService->logAudit('SESSION_TERMINATED', 'user_sessions', $sessionId);

        return response()->json([
            'status' => 'success',
            'message' => 'Sesi berhasil dihentikan.'
        ]);
    }

    /**
     * Get login history
     */
    public function getLoginHistory(Request $request): JsonResponse
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 20);

        $query = DB::table('audit_logs')
            ->where('user_id', Auth::id())
            ->whereIn('action', ['LOGIN_SUCCESS', 'LOGIN_FAILED'])
            ->orderBy('created_at', 'desc');

        $totalRecords = $query->count();
        $history = $query
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $history,
            'pagination' => [
                'current_page' => (int)$page,
                'total_records' => (int)$totalRecords
            ]
        ]);
    }

    /**
     * Get security activity
     */
    public function getSecurityActivity(Request $request): JsonResponse
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 20);

        $securityActions = [
            'PASSWORD_CHANGED',
            'PIN_CHANGED',
            '2FA_ENABLED',
            '2FA_DISABLED',
            'SESSION_TERMINATED',
            'LOGIN_SUCCESS',
            'LOGIN_FAILED'
        ];

        $query = DB::table('audit_logs')
            ->where('user_id', Auth::id())
            ->whereIn('action', $securityActions)
            ->orderBy('created_at', 'desc');

        $totalRecords = $query->count();
        $activities = $query
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $activities,
            'pagination' => [
                'current_page' => (int)$page,
                'total_records' => (int)$totalRecords
            ]
        ]);
    }
}