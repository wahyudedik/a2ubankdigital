<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Account;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountClosureController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Request account closure
     */
    public function requestClosure(Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'confirmation' => 'required|accepted'
        ]);

        try {
            $user = Auth::user();

            // Check if user has active loans
            $activeLoans = DB::table('loans')
                ->where('user_id', $user->id)
                ->whereIn('status', ['PENDING', 'APPROVED', 'DISBURSED'])
                ->count();

            if ($activeLoans > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat menutup akun. Anda masih memiliki pinjaman aktif yang harus diselesaikan terlebih dahulu.'
                ], 400);
            }

            // Check if user has active deposits
            $activeDeposits = Account::where('user_id', $user->id)
                ->where('account_type', 'DEPOSITO')
                ->where('status', 'ACTIVE')
                ->count();

            if ($activeDeposits > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat menutup akun. Anda masih memiliki deposito aktif. Silakan cairkan terlebih dahulu.'
                ], 400);
            }

            DB::beginTransaction();

            // Create closure request
            $closureId = DB::table('account_closure_requests')->insertGetId([
                'user_id' => $user->id,
                'reason' => $request->reason,
                'status' => 'PENDING',
                'requested_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Log the request
            $this->logService->log(
                'account_closure_requested',
                "User requested account closure",
                $user->id,
                ['closure_id' => $closureId, 'reason' => $request->reason]
            );

            // Notify admins
            $admins = User::whereIn('role_id', [1, 2])->where('status', 'ACTIVE')->get();
            foreach ($admins as $admin) {
                $this->notificationService->send(
                    $admin->id,
                    'Permintaan Penutupan Akun',
                    "Nasabah {$user->full_name} mengajukan penutupan akun",
                    'account_closure',
                    ['closure_id' => $closureId, 'user_id' => $user->id]
                );
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Permintaan penutupan akun berhasil diajukan. Tim kami akan menghubungi Anda dalam 1-3 hari kerja.',
                'data' => [
                    'closure_id' => $closureId,
                    'requested_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logService->log('account_closure_request_failed', $e->getMessage(), Auth::id());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengajukan penutupan akun: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get closure request status
     */
    public function getStatus(): JsonResponse
    {
        try {
            $request = DB::table('account_closure_requests')
                ->where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$request) {
                return response()->json([
                    'status' => 'success',
                    'data' => null
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $request->id,
                    'reason' => $request->reason,
                    'status' => $request->status,
                    'requested_at' => $request->requested_at,
                    'processed_at' => $request->processed_at,
                    'admin_notes' => $request->admin_notes
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil status permintaan'
            ], 500);
        }
    }

    /**
     * Cancel closure request
     */
    public function cancelRequest($id): JsonResponse
    {
        try {
            $request = DB::table('account_closure_requests')
                ->where('id', $id)
                ->where('user_id', Auth::id())
                ->where('status', 'PENDING')
                ->first();

            if (!$request) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Permintaan tidak ditemukan atau tidak dapat dibatalkan'
                ], 404);
            }

            DB::table('account_closure_requests')
                ->where('id', $id)
                ->update([
                    'status' => 'CANCELLED',
                    'processed_at' => now(),
                    'updated_at' => now()
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Permintaan penutupan akun berhasil dibatalkan'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membatalkan permintaan'
            ], 500);
        }
    }
}
