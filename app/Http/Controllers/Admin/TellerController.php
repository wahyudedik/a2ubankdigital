<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\LoanInstallment;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TellerController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Process cash deposit
     */
    public function deposit(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Only Teller, CS, Unit Head, Branch Head, Super Admin can access
        if (!in_array($user->role_id, [1, 2, 3, 5, 6])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $request->validate([
            'account_number' => 'required|string',
            'amount' => 'required|numeric|min:1'
        ]);

        $accountNumber = $request->account_number;
        $amount = (float)$request->amount;

        DB::beginTransaction();
        try {
            // Find and lock account
            $account = Account::where('account_number', $accountNumber)
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->first();

            if (!$account) {
                throw new \Exception("Rekening tidak ditemukan atau tidak aktif.");
            }

            $initialBalance = $account->balance;

            // Update account balance
            $account->increment('balance', $amount);
            $finalBalance = $account->fresh()->balance;

            // Create transaction record
            $description = "Setor tunai oleh Teller #" . $user->id;
            $transaction = Transaction::create([
                'transaction_code' => 'TRX-' . time() . '-' . rand(100000, 999999),
                'to_account_id' => $account->id,
                'transaction_type' => 'TOPUP',
                'amount' => $amount,
                'fee' => 0,
                'description' => $description,
                'status' => 'SUCCESS'
            ]);

            // Log audit
            $this->logService->logAudit('TELLER_DEPOSIT', 'transactions', $transaction->id, [], [
                'customer_user_id' => $account->user_id,
                'amount' => $amount,
                'account_number' => $accountNumber
            ]);

            // Create notification
            $this->notificationService->notifyUser(
                $account->user_id,
                'Setoran Tunai Berhasil',
                'Anda telah berhasil melakukan setoran tunai sebesar Rp ' . number_format($amount, 2, ',', '.') . '.'
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Setoran tunai berhasil.',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'initial_balance' => $initialBalance,
                    'final_balance' => $finalBalance
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Transaksi gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process cash withdrawal
     */
    public function withdrawal(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Only Teller, CS, Unit Head, Branch Head, Super Admin can access
        if (!in_array($user->role_id, [1, 2, 3, 5, 6])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $request->validate([
            'account_number' => 'required|string',
            'amount' => 'required|numeric|min:1'
        ]);

        $accountNumber = $request->account_number;
        $amount = (float)$request->amount;

        DB::beginTransaction();
        try {
            // Find and lock account
            $account = Account::where('account_number', $accountNumber)
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->first();

            if (!$account) {
                throw new \Exception("Rekening tidak ditemukan atau tidak aktif.");
            }

            if ($account->balance < $amount) {
                throw new \Exception("Saldo tidak mencukupi untuk penarikan.");
            }

            $initialBalance = $account->balance;

            // Update account balance
            $account->decrement('balance', $amount);
            $finalBalance = $account->fresh()->balance;

            // Create transaction record
            $description = "Tarik tunai oleh Teller #" . $user->id;
            $transaction = Transaction::create([
                'transaction_code' => 'TRX-' . time() . '-' . rand(100000, 999999),
                'from_account_id' => $account->id,
                'transaction_type' => 'WITHDRAWAL',
                'amount' => $amount,
                'fee' => 0,
                'description' => $description,
                'status' => 'SUCCESS'
            ]);

            // Log audit
            $this->logService->logAudit('TELLER_WITHDRAWAL', 'transactions', $transaction->id, [], [
                'customer_user_id' => $account->user_id,
                'amount' => $amount,
                'account_number' => $accountNumber
            ]);

            // Create notification
            $this->notificationService->notifyUser(
                $account->user_id,
                'Penarikan Tunai Berhasil',
                'Anda telah berhasil melakukan penarikan tunai sebesar Rp ' . number_format($amount, 2, ',', '.') . '.'
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Penarikan tunai berhasil.',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'initial_balance' => $initialBalance,
                    'final_balance' => $finalBalance
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Transaksi gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process cash installment payment
     */
    public function payInstallment(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Only Teller, CS, Unit Head and above can access
        if (!in_array($user->role_id, [1, 2, 3, 5, 6])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.'
            ], 403);
        }

        $request->validate([
            'installment_id' => 'required|integer|min:1',
            'cash_amount' => 'required|numeric|min:1'
        ]);

        $installmentId = $request->installment_id;
        $cashAmount = (float)$request->cash_amount;

        DB::beginTransaction();
        try {
            // Find and lock installment
            $installment = LoanInstallment::with('loan')
                ->where('id', $installmentId)
                ->whereIn('status', ['PENDING', 'OVERDUE'])
                ->lockForUpdate()
                ->first();

            if (!$installment) {
                throw new \Exception("Angsuran tidak ditemukan, sudah lunas, atau sedang diproses.");
            }

            $totalDue = $installment->total_amount + ($installment->late_fee ?? 0);

            if ($cashAmount < $totalDue) {
                throw new \Exception("Jumlah uang tunai yang diterima kurang dari total tagihan (Rp " . number_format($totalDue, 2, ',', '.') . ").");
            }

            // Create transaction record
            $description = "Bayar Angsuran Tunai Pinjaman #" . $installment->loan_id . " ke-" . $installment->installment_number . " via Teller #" . $user->id;
            $transaction = Transaction::create([
                'transaction_code' => 'TRX-' . time() . '-' . rand(100000, 999999),
                'to_account_id' => null,
                'transaction_type' => 'LOAN_PAYMENT',
                'amount' => $totalDue,
                'fee' => 0,
                'description' => $description,
                'status' => 'SUCCESS'
            ]);

            // Update installment status
            $installment->update([
                'status' => 'PAID',
                'paid_at' => now(),
                'paid_amount' => $totalDue
            ]);

            // Log audit
            $this->logService->logAudit('TELLER_LOAN_PAYMENT', 'transactions', $transaction->id, [], [
                'customer_user_id' => $installment->loan->user_id,
                'installment_id' => $installmentId,
                'amount_paid' => $totalDue
            ]);

            // Create notification
            $this->notificationService->notifyUser(
                $installment->loan->user_id,
                'Pembayaran Angsuran Diterima',
                'Kami telah menerima pembayaran tunai Anda sebesar Rp ' . number_format($totalDue, 2, ',', '.') . ' untuk angsuran pinjaman Anda. Terima kasih.'
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran angsuran berhasil diterima.',
                'data' => ['transaction_id' => $transaction->id]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }
}
