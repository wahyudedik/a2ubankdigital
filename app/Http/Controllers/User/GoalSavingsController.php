<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\GoalSavingsDetail;
use App\Models\Transaction;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GoalSavingsController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Open goal savings account
     */
    public function openGoalSavings(Request $request): JsonResponse
    {
        $request->validate([
            'goal_name' => 'required|string|max:255',
            'goal_amount' => 'required|numeric|min:100000|max:1000000000',
            'target_date' => 'required|date|after:today',
            'autodebit_day' => 'required|integer|min:1|max:31',
            'autodebit_amount' => 'required|numeric|min:10000',
            'from_account_id' => 'required|exists:accounts,id',
            'initial_deposit' => 'sometimes|numeric|min:0'
        ]);

        $user = Auth::user();

        // Verify source account ownership
        $fromAccount = Account::where('id', $request->from_account_id)
            ->where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$fromAccount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rekening sumber tidak ditemukan atau tidak aktif.'
            ], 404);
        }

        // Check if user already has a goal savings account
        $existingGoalSavings = Account::where('user_id', $user->id)
            ->where('account_type', 'TABUNGAN_RENCANA')
            ->where('status', 'ACTIVE')
            ->first();

        if ($existingGoalSavings) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda sudah memiliki rekening tabungan rencana yang aktif.'
            ], 400);
        }

        // Validate autodebit amount vs goal
        $monthsToTarget = now()->diffInMonths($request->target_date);
        $requiredMonthlyAmount = $request->goal_amount / max($monthsToTarget, 1);
        
        if ($request->autodebit_amount < $requiredMonthlyAmount * 0.5) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jumlah autodebit terlalu kecil untuk mencapai target. Minimal Rp ' . number_format($requiredMonthlyAmount * 0.5, 0, ',', '.') . ' per bulan.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Generate account number
            $accountNumber = '1200' . str_pad($user->id, 6, '0', STR_PAD_LEFT) . rand(100, 999);

            // Create goal savings account
            $goalAccount = Account::create([
                'user_id' => $user->id,
                'account_number' => $accountNumber,
                'account_type' => 'TABUNGAN_RENCANA',
                'balance' => $request->initial_deposit ?? 0,
                'status' => 'ACTIVE'
            ]);

            // Create goal savings details
            $goalDetails = GoalSavingsDetail::create([
                'account_id' => $goalAccount->id,
                'goal_name' => $request->goal_name,
                'goal_amount' => $request->goal_amount,
                'target_date' => $request->target_date,
                'autodebit_day' => $request->autodebit_day,
                'autodebit_amount' => $request->autodebit_amount,
                'from_account_id' => $request->from_account_id
            ]);

            // Process initial deposit if provided
            if ($request->initial_deposit && $request->initial_deposit > 0) {
                // Check source account balance
                if ($fromAccount->balance < $request->initial_deposit) {
                    throw new \Exception("Saldo rekening sumber tidak mencukupi untuk setoran awal.");
                }

                // Debit from source account
                $fromAccount->decrement('balance', $request->initial_deposit);

                // Create transaction record
                $transaction = Transaction::create([
                    'transaction_code' => 'TRX-' . time() . '-' . rand(100000, 999999),
                    'from_account_id' => $fromAccount->id,
                    'to_account_id' => $goalAccount->id,
                    'transaction_type' => 'TRANSFER_INTERNAL',
                    'amount' => $request->initial_deposit,
                    'fee' => 0,
                    'description' => 'Setoran awal tabungan rencana: ' . $request->goal_name,
                    'status' => 'SUCCESS'
                ]);
            }

            // Log goal savings creation
            $this->logService->logAudit('GOAL_SAVINGS_CREATED', 'accounts', $goalAccount->id, [], [
                'goal_name' => $request->goal_name,
                'goal_amount' => $request->goal_amount,
                'target_date' => $request->target_date
            ]);

            // Notify user
            $this->notificationService->notifyUser(
                $user->id,
                'Tabungan Rencana Berhasil Dibuat',
                'Tabungan rencana "' . $request->goal_name . '" dengan target Rp ' . number_format($request->goal_amount, 2, ',', '.') . ' berhasil dibuat.'
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Tabungan rencana berhasil dibuat.',
                'data' => [
                    'account' => $goalAccount,
                    'goal_details' => $goalDetails,
                    'progress_percentage' => $goalDetails->progress_percentage,
                    'remaining_amount' => $goalDetails->remaining_amount,
                    'days_remaining' => $goalDetails->days_remaining
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat tabungan rencana: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get goal savings details
     */
    public function getGoalSavingsDetail(): JsonResponse
    {
        $user = Auth::user();

        $goalAccount = Account::with(['goalSavingsDetail.fromAccount'])
            ->where('user_id', $user->id)
            ->where('account_type', 'TABUNGAN_RENCANA')
            ->where('status', 'ACTIVE')
            ->first();

        if (!$goalAccount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda belum memiliki tabungan rencana.'
            ], 404);
        }

        $goalDetails = $goalAccount->goalSavingsDetail;

        // Get recent transactions
        $recentTransactions = Transaction::where('to_account_id', $goalAccount->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'account' => $goalAccount,
                'goal_details' => $goalDetails,
                'progress_percentage' => $goalDetails->progress_percentage,
                'remaining_amount' => $goalDetails->remaining_amount,
                'days_remaining' => $goalDetails->days_remaining,
                'is_achieved' => $goalDetails->is_achieved,
                'recent_transactions' => $recentTransactions
            ]
        ]);
    }

    /**
     * Manual deposit to goal savings
     */
    public function manualDeposit(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000',
            'from_account_id' => 'required|exists:accounts,id'
        ]);

        $user = Auth::user();

        // Get goal savings account
        $goalAccount = Account::where('user_id', $user->id)
            ->where('account_type', 'TABUNGAN_RENCANA')
            ->where('status', 'ACTIVE')
            ->first();

        if (!$goalAccount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda belum memiliki tabungan rencana.'
            ], 404);
        }

        // Verify source account
        $fromAccount = Account::where('id', $request->from_account_id)
            ->where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$fromAccount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rekening sumber tidak ditemukan atau tidak aktif.'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Check balance
            if ($fromAccount->balance < $request->amount) {
                throw new \Exception("Saldo tidak mencukupi.");
            }

            // Transfer funds
            $fromAccount->decrement('balance', $request->amount);
            $goalAccount->increment('balance', $request->amount);

            // Create transaction
            $transaction = Transaction::create([
                'transaction_code' => 'TRX-' . time() . '-' . rand(100000, 999999),
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $goalAccount->id,
                'transaction_type' => 'TRANSFER_INTERNAL',
                'amount' => $request->amount,
                'fee' => 0,
                'description' => 'Setoran manual tabungan rencana',
                'status' => 'SUCCESS'
            ]);

            // Check if goal is achieved
            $goalDetails = $goalAccount->goalSavingsDetail;
            if ($goalDetails && $goalDetails->is_achieved) {
                $this->notificationService->notifyUser(
                    $user->id,
                    'Target Tabungan Rencana Tercapai!',
                    'Selamat! Target tabungan rencana "' . $goalDetails->goal_name . '" sebesar Rp ' . number_format($goalDetails->goal_amount, 2, ',', '.') . ' telah tercapai.'
                );
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Setoran berhasil.',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'new_balance' => $goalAccount->fresh()->balance,
                    'progress_percentage' => $goalDetails->progress_percentage ?? 0
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Setoran gagal: ' . $e->getMessage()
            ], 500);
        }
    }
}