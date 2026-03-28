<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionReversalController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Manual transaction reversal
     */
    public function reverseTransaction(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Only Super Admin and Branch Head can reverse transactions
        if (!in_array($user->role_id, [1, 2])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak. Hanya Super Admin dan Kepala Cabang yang dapat melakukan reversal transaksi.'
            ], 403);
        }

        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'reversal_reason' => 'required|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            // Get original transaction with lock
            $originalTransaction = Transaction::with(['fromAccount', 'toAccount'])
                ->where('id', $request->transaction_id)
                ->where('status', 'SUCCESS')
                ->lockForUpdate()
                ->first();

            if (!$originalTransaction) {
                throw new \Exception("Transaksi tidak ditemukan atau sudah tidak dapat di-reverse.");
            }

            // Check if transaction is already reversed
            $existingReversal = Transaction::where('reference_number', 'REVERSAL-' . $originalTransaction->id)->first();
            if ($existingReversal) {
                throw new \Exception("Transaksi ini sudah pernah di-reverse.");
            }

            // Check transaction age (can only reverse transactions within 24 hours)
            $transactionAge = now()->diffInHours($originalTransaction->created_at);
            if ($transactionAge > 24) {
                throw new \Exception("Transaksi lebih dari 24 jam tidak dapat di-reverse.");
            }

            // Perform reversal based on transaction type
            $this->performReversal($originalTransaction, $request->reversal_reason, $user->id);

            // Update original transaction status
            $originalTransaction->update([
                'status' => 'REVERSED',
                'description' => $originalTransaction->description . ' [REVERSED: ' . $request->reversal_reason . ']'
            ]);

            // Log the reversal
            $this->logService->logAudit('TRANSACTION_REVERSED', 'transactions', $originalTransaction->id, [], [
                'reversal_reason' => $request->reversal_reason,
                'reversed_by' => $user->id,
                'original_amount' => $originalTransaction->amount
            ]);

            // Notify affected users
            $this->notifyReversalUsers($originalTransaction, $request->reversal_reason);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil di-reverse.',
                'data' => [
                    'original_transaction_id' => $originalTransaction->id,
                    'reversal_reason' => $request->reversal_reason,
                    'reversed_by' => $user->full_name,
                    'reversed_at' => now()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal melakukan reversal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reversible transactions
     */
    public function getReversibleTransactions(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Only Super Admin and Branch Head can access
        if (!in_array($user->role_id, [1, 2])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $transactionType = $request->input('transaction_type');

        // Get transactions from last 24 hours that can be reversed
        $query = Transaction::with(['fromAccount.user', 'toAccount.user'])
            ->where('status', 'SUCCESS')
            ->where('created_at', '>=', now()->subHours(24))
            ->whereNotIn('transaction_type', ['BUNGA_TABUNGAN', 'BIAYA_ADMIN']) // Exclude system transactions
            ->whereNotExists(function($q) {
                $q->select(DB::raw(1))
                  ->from('transactions as t2')
                  ->whereRaw('t2.reference_number = CONCAT("REVERSAL-", transactions.id)');
            });

        if ($transactionType) {
            $query->where('transaction_type', $transactionType);
        }

        $totalRecords = $query->count();
        $transactions = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        // Add reversal eligibility info
        $transactions->each(function ($transaction) {
            $hoursOld = now()->diffInHours($transaction->created_at);
            $transaction->hours_old = $hoursOld;
            $transaction->can_reverse = $hoursOld <= 24;
            $transaction->reversal_deadline = $transaction->created_at->addHours(24);
        });

        return response()->json([
            'status' => 'success',
            'data' => $transactions,
            'pagination' => [
                'current_page' => (int)$page,
                'total_records' => (int)$totalRecords
            ]
        ]);
    }

    /**
     * Perform the actual reversal based on transaction type
     */
    private function performReversal(Transaction $originalTransaction, string $reason, int $reversedBy): void
    {
        $reversalDescription = 'REVERSAL: ' . $originalTransaction->description . ' - Reason: ' . $reason;

        switch ($originalTransaction->transaction_type) {
            case 'TRANSFER_INTERNAL':
                $this->reverseInternalTransfer($originalTransaction, $reversalDescription, $reversedBy);
                break;
            
            case 'TRANSFER_EXTERNAL':
                $this->reverseExternalTransfer($originalTransaction, $reversalDescription, $reversedBy);
                break;
            
            case 'TOPUP':
                $this->reverseCashDeposit($originalTransaction, $reversalDescription, $reversedBy);
                break;
            
            case 'WITHDRAWAL':
                $this->reverseCashWithdrawal($originalTransaction, $reversalDescription, $reversedBy);
                break;
            
            case 'LOAN_PAYMENT':
            case 'LOAN_PAYMENT':
                $this->reverseLoanPayment($originalTransaction, $reversalDescription, $reversedBy);
                break;
            
            case 'TOPUP_EWALLET':
                $this->reverseEWalletTopup($originalTransaction, $reversalDescription, $reversedBy);
                break;
            
            default:
                throw new \Exception("Tipe transaksi '{$originalTransaction->transaction_type}' tidak dapat di-reverse.");
        }
    }

    private function reverseInternalTransfer(Transaction $original, string $description, int $reversedBy): void
    {
        // Credit back to source account
        if ($original->fromAccount) {
            $original->fromAccount->increment('balance', $original->amount + $original->fee);
        }

        // Debit from destination account
        if ($original->toAccount) {
            $original->toAccount->decrement('balance', $original->amount);
        }

        // Create reversal transaction
        Transaction::create([
            'transaction_code' => 'REV-' . time() . '-' . rand(100000, 999999),
            'from_account_id' => $original->to_account_id,
            'to_account_id' => $original->from_account_id,
            'transaction_type' => 'REVERSED',
            'amount' => $original->amount,
            'fee' => 0,
            'description' => $description,
            'status' => 'SUCCESS',
            'reference_number' => 'REVERSAL-' . $original->id
        ]);
    }

    private function reverseExternalTransfer(Transaction $original, string $description, int $reversedBy): void
    {
        // Credit back to source account (amount + fee)
        if ($original->fromAccount) {
            $original->fromAccount->increment('balance', $original->amount + $original->fee);
        }

        // Create reversal transaction
        Transaction::create([
            'transaction_code' => 'REV-' . time() . '-' . rand(100000, 999999),
            'from_account_id' => null,
            'to_account_id' => $original->from_account_id,
            'transaction_type' => 'REVERSED',
            'amount' => $original->amount,
            'fee' => 0,
            'description' => $description,
            'status' => 'SUCCESS',
            'reference_number' => 'REVERSAL-' . $original->id
        ]);
    }

    private function reverseCashDeposit(Transaction $original, string $description, int $reversedBy): void
    {
        // Debit from account
        if ($original->toAccount) {
            $original->toAccount->decrement('balance', $original->amount);
        }

        // Create reversal transaction
        Transaction::create([
            'transaction_code' => 'REV-' . time() . '-' . rand(100000, 999999),
            'from_account_id' => $original->to_account_id,
            'to_account_id' => null,
            'transaction_type' => 'REVERSED',
            'amount' => $original->amount,
            'fee' => 0,
            'description' => $description,
            'status' => 'SUCCESS',
            'reference_number' => 'REVERSAL-' . $original->id
        ]);
    }

    private function reverseCashWithdrawal(Transaction $original, string $description, int $reversedBy): void
    {
        // Credit back to account
        if ($original->fromAccount) {
            $original->fromAccount->increment('balance', $original->amount);
        }

        // Create reversal transaction
        Transaction::create([
            'transaction_code' => 'REV-' . time() . '-' . rand(100000, 999999),
            'from_account_id' => null,
            'to_account_id' => $original->from_account_id,
            'transaction_type' => 'REVERSED',
            'amount' => $original->amount,
            'fee' => 0,
            'description' => $description,
            'status' => 'SUCCESS',
            'reference_number' => 'REVERSAL-' . $original->id
        ]);
    }

    private function reverseLoanPayment(Transaction $original, string $description, int $reversedBy): void
    {
        // This would require more complex logic to reverse loan installment status
        // For now, just create the reversal transaction
        Transaction::create([
            'transaction_code' => 'REV-' . time() . '-' . rand(100000, 999999),
            'from_account_id' => null,
            'to_account_id' => $original->from_account_id,
            'transaction_type' => 'REVERSED',
            'amount' => $original->amount,
            'fee' => 0,
            'description' => $description,
            'status' => 'SUCCESS',
            'reference_number' => 'REVERSAL-' . $original->id
        ]);
    }

    private function reverseEWalletTopup(Transaction $original, string $description, int $reversedBy): void
    {
        // Credit back to source account (amount + fee)
        if ($original->fromAccount) {
            $original->fromAccount->increment('balance', $original->amount + $original->fee);
        }

        // Create reversal transaction
        Transaction::create([
            'transaction_code' => 'REV-' . time() . '-' . rand(100000, 999999),
            'from_account_id' => null,
            'to_account_id' => $original->from_account_id,
            'transaction_type' => 'REVERSED',
            'amount' => $original->amount,
            'fee' => 0,
            'description' => $description,
            'status' => 'SUCCESS',
            'reference_number' => 'REVERSAL-' . $original->id
        ]);
    }

    private function notifyReversalUsers(Transaction $original, string $reason): void
    {
        // Notify source account holder
        if ($original->fromAccount && $original->fromAccount->user) {
            $this->notificationService->notifyUser(
                $original->fromAccount->user->id,
                'Transaksi Di-reverse',
                'Transaksi Anda dengan kode ' . $original->transaction_code . ' telah di-reverse. Alasan: ' . $reason
            );
        }

        // Notify destination account holder (for internal transfers)
        if ($original->toAccount && $original->toAccount->user && $original->toAccount->user->id !== $original->fromAccount->user->id) {
            $this->notificationService->notifyUser(
                $original->toAccount->user->id,
                'Transaksi Di-reverse',
                'Transaksi yang Anda terima dengan kode ' . $original->transaction_code . ' telah di-reverse. Alasan: ' . $reason
            );
        }
    }
}

