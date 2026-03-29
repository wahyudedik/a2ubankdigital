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
     * Get all goal savings for authenticated user
     */
    public function index(): JsonResponse
    {
        try {
            $goalSavings = Account::where('user_id', Auth::id())
                ->where('account_type', 'TABUNGAN_BERJANGKA')
                ->with(['goalSavingsDetail', 'goalSavingsDetail.fromAccount'])
                ->get()
                ->map(function ($account) {
                    $detail = $account->goalSavingsDetail;
                    return [
                        'id' => $account->id,
                        'account_number' => $account->account_number,
                        'goal_name' => $detail->goal_name ?? 'Tabungan Berjangka',
                        'goal_amount' => (float)($detail->goal_amount ?? 0),
                        'current_balance' => (float)$account->balance,
                        'target_date' => $detail->target_date ?? null,
                        'progress_percentage' => $detail->progress_percentage ?? 0,
                        'remaining_amount' => $detail->remaining_amount ?? 0,
                        'days_remaining' => $detail->days_remaining ?? 0,
                        'is_achieved' => $detail->is_achieved ?? false,
                        'autodebit_enabled' => $detail->autodebit_amount > 0,
                        'autodebit_day' => $detail->autodebit_day ?? null,
                        'autodebit_amount' => (float)($detail->autodebit_amount ?? 0),
                        'status' => $account->status,
                        'created_at' => $account->created_at->toISOString()
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $goalSavings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data tabungan berjangka.'
            ], 500);
        }
    }

    /**
     * Create new goal savings
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'goal_name' => 'required|string|max:100',
            'goal_amount' => 'required|numeric|min:100000',
            'target_date' => 'required|date|after:today',
            'initial_deposit' => 'required|numeric|min:10000',
            'autodebit_enabled' => 'sometimes|boolean',
            'autodebit_day' => 'required_if:autodebit_enabled,true|integer|min:1|max:28',
            'autodebit_amount' => 'required_if:autodebit_enabled,true|numeric|min:10000'
        ]);

        DB::beginTransaction();
        try {
            // Get source account
            $sourceAccount = Account::where('user_id', Auth::id())
                ->where('account_type', 'TABUNGAN')
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->first();

            if (!$sourceAccount || $sourceAccount->balance < $request->initial_deposit) {
                throw new \Exception('Saldo tidak mencukupi untuk setoran awal.');
            }

            // Create goal savings account
            $goalAccount = Account::create([
                'user_id' => Auth::id(),
                'account_number' => 'GS' . time() . rand(1000, 9999),
                'account_type' => 'TABUNGAN_BERJANGKA',
                'balance' => $request->initial_deposit,
                'status' => 'ACTIVE'
            ]);

            // Create goal savings detail
            GoalSavingsDetail::create([
                'account_id' => $goalAccount->id,
                'goal_name' => $request->goal_name,
                'goal_amount' => $request->goal_amount,
                'target_date' => $request->target_date,
                'autodebit_day' => $request->autodebit_enabled ? $request->autodebit_day : null,
                'autodebit_amount' => $request->autodebit_enabled ? $request->autodebit_amount : 0,
                'from_account_id' => $sourceAccount->id
            ]);

            // Deduct initial deposit from source account
            $sourceAccount->decrement('balance', $request->initial_deposit);

            // Create transaction record
            $transaction = Transaction::create([
                'transaction_code' => 'GSOPEN-' . time() . '-' . rand(100000, 999999),
                'from_account_id' => $sourceAccount->id,
                'to_account_id' => $goalAccount->id,
                'transaction_type' => 'GOAL_SAVINGS_OPEN',
                'amount' => $request->initial_deposit,
                'fee' => 0,
                'description' => "Pembukaan Tabungan Berjangka: {$request->goal_name}",
                'status' => 'SUCCESS'
            ]);

            // Log the action
            $this->logService->log(
                'goal_savings_created',
                "Goal savings created: {$request->goal_name}",
                Auth::id(),
                ['account_id' => $goalAccount->id, 'goal_amount' => $request->goal_amount]
            );

            // Send notification
            $this->notificationService->send(
                Auth::id(),
                'Tabungan Berjangka Dibuat',
                "Tabungan berjangka '{$request->goal_name}' berhasil dibuat dengan target " . number_format($request->goal_amount, 0, ',', '.'),
                'goal_savings',
                ['account_id' => $goalAccount->id]
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Tabungan berjangka berhasil dibuat.',
                'data' => [
                    'account_id' => $goalAccount->id,
                    'account_number' => $goalAccount->account_number,
                    'transaction_id' => $transaction->id
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logService->log('goal_savings_creation_failed', $e->getMessage(), Auth::id());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat tabungan berjangka: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deposit to goal savings
     */
    public function deposit(Request $request, $id): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000'
        ]);

        DB::beginTransaction();
        try {
            // Get goal savings account
            $goalAccount = Account::where('id', $id)
                ->where('user_id', Auth::id())
                ->where('account_type', 'TABUNGAN_BERJANGKA')
                ->lockForUpdate()
                ->first();

            if (!$goalAccount) {
                throw new \Exception('Tabungan berjangka tidak ditemukan.');
            }

            // Get source account
            $sourceAccount = Account::where('user_id', Auth::id())
                ->where('account_type', 'TABUNGAN')
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->first();

            if (!$sourceAccount || $sourceAccount->balance < $request->amount) {
                throw new \Exception('Saldo tidak mencukupi.');
            }

            // Transfer funds
            $sourceAccount->decrement('balance', $request->amount);
            $goalAccount->increment('balance', $request->amount);

            // Create transaction record
            $transaction = Transaction::create([
                'transaction_code' => 'GSDEP-' . time() . '-' . rand(100000, 999999),
                'from_account_id' => $sourceAccount->id,
                'to_account_id' => $goalAccount->id,
                'transaction_type' => 'GOAL_SAVINGS_DEPOSIT',
                'amount' => $request->amount,
                'fee' => 0,
                'description' => "Setoran Tabungan Berjangka: {$goalAccount->goalSavingsDetail->goal_name}",
                'status' => 'SUCCESS'
            ]);

            // Check if goal is achieved
            $detail = $goalAccount->goalSavingsDetail;
            if ($goalAccount->balance >= $detail->goal_amount) {
                $this->notificationService->send(
                    Auth::id(),
                    '🎉 Target Tercapai!',
                    "Selamat! Target tabungan '{$detail->goal_name}' sebesar " . number_format($detail->goal_amount, 0, ',', '.') . " telah tercapai!",
                    'goal_savings',
                    ['account_id' => $goalAccount->id]
                );
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Setoran berhasil.',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'new_balance' => (float)$goalAccount->fresh()->balance,
                    'progress_percentage' => $goalAccount->fresh()->goalSavingsDetail->progress_percentage
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

    /**
     * Update goal savings settings
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'goal_name' => 'sometimes|string|max:100',
            'goal_amount' => 'sometimes|numeric|min:100000',
            'target_date' => 'sometimes|date|after:today',
            'autodebit_enabled' => 'sometimes|boolean',
            'autodebit_day' => 'sometimes|integer|min:1|max:28',
            'autodebit_amount' => 'sometimes|numeric|min:10000'
        ]);

        try {
            $goalAccount = Account::where('id', $id)
                ->where('user_id', Auth::id())
                ->where('account_type', 'TABUNGAN_BERJANGKA')
                ->first();

            if (!$goalAccount) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tabungan berjangka tidak ditemukan.'
                ], 404);
            }

            $detail = $goalAccount->goalSavingsDetail;
            
            $updateData = [];
            if ($request->has('goal_name')) $updateData['goal_name'] = $request->goal_name;
            if ($request->has('goal_amount')) $updateData['goal_amount'] = $request->goal_amount;
            if ($request->has('target_date')) $updateData['target_date'] = $request->target_date;
            
            if ($request->has('autodebit_enabled')) {
                if ($request->autodebit_enabled) {
                    $updateData['autodebit_day'] = $request->autodebit_day;
                    $updateData['autodebit_amount'] = $request->autodebit_amount;
                } else {
                    $updateData['autodebit_day'] = null;
                    $updateData['autodebit_amount'] = 0;
                }
            }

            $detail->update($updateData);

            return response()->json([
                'status' => 'success',
                'message' => 'Pengaturan tabungan berjangka berhasil diperbarui.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui pengaturan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Close goal savings (withdraw all funds)
     */
    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $goalAccount = Account::where('id', $id)
                ->where('user_id', Auth::id())
                ->where('account_type', 'TABUNGAN_BERJANGKA')
                ->lockForUpdate()
                ->first();

            if (!$goalAccount) {
                throw new \Exception('Tabungan berjangka tidak ditemukan.');
            }

            $balance = $goalAccount->balance;

            if ($balance > 0) {
                // Transfer remaining balance to main account
                $mainAccount = Account::where('user_id', Auth::id())
                    ->where('account_type', 'TABUNGAN')
                    ->where('status', 'ACTIVE')
                    ->lockForUpdate()
                    ->first();

                if ($mainAccount) {
                    $mainAccount->increment('balance', $balance);
                    
                    // Create transaction record
                    Transaction::create([
                        'transaction_code' => 'GSCLOSE-' . time() . '-' . rand(100000, 999999),
                        'from_account_id' => $goalAccount->id,
                        'to_account_id' => $mainAccount->id,
                        'transaction_type' => 'GOAL_SAVINGS_CLOSE',
                        'amount' => $balance,
                        'fee' => 0,
                        'description' => "Penutupan Tabungan Berjangka: {$goalAccount->goalSavingsDetail->goal_name}",
                        'status' => 'SUCCESS'
                    ]);
                }
            }

            // Delete goal savings detail
            $goalAccount->goalSavingsDetail()->delete();
            
            // Close account
            $goalAccount->update(['status' => 'CLOSED']);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Tabungan berjangka berhasil ditutup.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menutup tabungan berjangka: ' . $e->getMessage()
            ], 500);
        }
    }
}
