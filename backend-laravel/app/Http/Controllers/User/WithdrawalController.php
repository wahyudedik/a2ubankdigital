<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalAccount;
use App\Models\WithdrawalRequest;
use App\Models\Account;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get withdrawal accounts
     */
    public function getAccounts(): JsonResponse
    {
        $accounts = WithdrawalAccount::where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $accounts
        ]);
    }

    /**
     * Add withdrawal account
     */
    public function addAccount(Request $request): JsonResponse
    {
        $request->validate([
            'bank_code' => 'required|string|size:3',
            'bank_name' => 'required|string',
            'account_number' => 'required|string|min:8|max:20',
            'account_holder_name' => 'required|string|max:255'
        ]);

        // Check if account already exists
        $existing = WithdrawalAccount::where('user_id', Auth::id())
            ->where('bank_code', $request->bank_code)
            ->where('account_number', $request->account_number)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rekening penarikan sudah terdaftar.'
            ], 409);
        }

        $account = WithdrawalAccount::create([
            'user_id' => Auth::id(),
            'bank_code' => $request->bank_code,
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'account_holder_name' => $request->account_holder_name,
            'is_active' => true
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Rekening penarikan berhasil ditambahkan.',
            'data' => $account
        ], 201);
    }

    /**
     * Create withdrawal request
     */
    public function createRequest(Request $request): JsonResponse
    {
        $request->validate([
            'withdrawal_account_id' => 'required|exists:withdrawal_accounts,id',
            'amount' => 'required|numeric|min:50000|max:20000000',
            'purpose' => 'sometimes|string|max:255'
        ]);

        // Verify withdrawal account belongs to user
        $withdrawalAccount = WithdrawalAccount::where('id', $request->withdrawal_account_id)
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->first();

        if (!$withdrawalAccount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rekening penarikan tidak valid.'
            ], 400);
        }

        // Check user's balance
        $userAccount = Account::where('user_id', Auth::id())
            ->where('account_type', 'TABUNGAN')
            ->first();

        if (!$userAccount || $userAccount->balance < $request->amount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Saldo tidak mencukupi untuk penarikan.'
            ], 400);
        }

        // Check for pending withdrawal requests
        $pendingRequest = WithdrawalRequest::where('user_id', Auth::id())
            ->where('status', 'PENDING')
            ->first();

        if ($pendingRequest) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda masih memiliki permintaan penarikan yang sedang diproses.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $withdrawalRequest = WithdrawalRequest::create([
                'user_id' => Auth::id(),
                'withdrawal_account_id' => $request->withdrawal_account_id,
                'amount' => $request->amount,
                'purpose' => $request->purpose,
                'status' => 'PENDING'
            ]);

            // Notify admin staff
            $this->notificationService->notifyStaffByRole(
                [1, 2, 3, 5], // Super Admin, Admin, Manager, Teller
                'Permintaan Penarikan Baru',
                'Nasabah ' . Auth::user()->full_name . ' mengajukan penarikan sebesar ' . number_format($request->amount, 0, ',', '.') . '.'
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Permintaan penarikan berhasil diajukan.',
                'data' => $withdrawalRequest->load('withdrawalAccount')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengajukan permintaan penarikan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get withdrawal requests history
     */
    public function getRequests(Request $request): JsonResponse
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);

        $query = WithdrawalRequest::where('user_id', Auth::id())
            ->with('withdrawalAccount');

        $totalRecords = $query->count();
        $requests = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $requests,
            'pagination' => [
                'current_page' => (int)$page,
                'total_records' => (int)$totalRecords
            ]
        ]);
    }

    /**
     * Cancel withdrawal request
     */
    public function cancelRequest($id): JsonResponse
    {
        $withdrawalRequest = WithdrawalRequest::where('user_id', Auth::id())
            ->where('id', $id)
            ->where('status', 'PENDING')
            ->first();

        if (!$withdrawalRequest) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permintaan penarikan tidak ditemukan atau tidak dapat dibatalkan.'
            ], 404);
        }

        $withdrawalRequest->update([
            'status' => 'CANCELLED',
            'cancelled_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Permintaan penarikan berhasil dibatalkan.'
        ]);
    }
}