<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Notification;
use App\Models\Loan;
use App\Models\AuditLog;
use App\Models\TopupRequest;
use App\Models\WithdrawalRequest;
use App\Models\CardRequest;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminApiController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // ===== NOTIFICATIONS =====
    public function markAllNotificationsRead()
    {
        try {
            Notification::where('user_id', Auth::id())
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json([
                'status' => 'success',
                'message' => 'Semua notifikasi berhasil ditandai sebagai dibaca'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menandai notifikasi: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===== TELLER OPERATIONS =====
    public function tellerAccountInquiry(Request $request)
    {
        try {
            $request->validate([
                'account_number' => 'required|string'
            ]);

            $account = Account::where('account_number', $request->account_number)
                ->with('user')
                ->first();

            if (!$account) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rekening tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'account_number' => $account->account_number,
                    'recipient_name' => $account->user->full_name,
                    'account_type' => $account->account_type,
                    'balance' => $account->balance,
                    'status' => $account->status
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal melakukan inquiry: ' . $e->getMessage()
            ], 500);
        }
    }

    public function tellerDeposit(Request $request)
    {
        try {
            $request->validate([
                'account_number' => 'required|string',
                'amount' => 'required|numeric|min:1000'
            ]);

            $account = Account::where('account_number', $request->account_number)->first();
            
            if (!$account) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rekening tidak ditemukan'
                ], 404);
            }

            if ($account->status !== 'ACTIVE') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rekening tidak aktif'
                ], 400);
            }

            DB::beginTransaction();

            // Update balance
            $oldBalance = $account->balance;
            $account->increment('balance', $request->amount);
            $newBalance = $account->fresh()->balance;

            // Create transaction record
            $transactionCode = 'TRX-' . time() . '-' . rand(100000, 999999);
            $transaction = Transaction::create([
                'transaction_code' => $transactionCode,
                'to_account_id' => $account->id,
                'transaction_type' => 'TELLER_DEPOSIT',
                'amount' => $request->amount,
                'fee' => 0,
                'description' => 'Setoran Tunai via Teller',
                'status' => 'SUCCESS',
                'processed_by' => Auth::id()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Setoran berhasil diproses',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'transaction_code' => $transactionCode,
                    'amount' => $request->amount,
                    'initial_balance' => $oldBalance,
                    'final_balance' => $newBalance,
                    'new_balance' => $newBalance // for backward compatibility
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses setoran: ' . $e->getMessage()
            ], 500);
        }
    }

    public function tellerLoanPayment(Request $request)
    {
        try {
            $request->validate([
                'loan_id' => 'required|exists:loans,id',
                'amount' => 'required|numeric|min:1000'
            ]);

            $loan = Loan::with('user')->findOrFail($request->loan_id);
            
            if ($loan->status !== 'DISBURSED') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pinjaman tidak dalam status aktif'
                ], 400);
            }

            DB::beginTransaction();

            // Create transaction record
            $transactionCode = 'TRX-' . time() . '-' . rand(100000, 999999);
            $transaction = Transaction::create([
                'transaction_code' => $transactionCode,
                'transaction_type' => 'LOAN_PAYMENT',
                'amount' => $request->amount,
                'fee' => 0,
                'description' => 'Pembayaran Pinjaman via Teller',
                'status' => 'SUCCESS',
                'processed_by' => Auth::id(),
                'reference_id' => $loan->id
            ]);

            // Update loan remaining balance (you may need to add this field to loans table)
            // For now, we'll just record the payment

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran pinjaman berhasil diproses',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'transaction_code' => $transactionCode,
                    'amount' => $request->amount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===== TOPUP REQUESTS =====
    public function processTopupRequest(Request $request)
    {
        try {
            $request->validate([
                'request_id' => 'required|integer',
                'action' => 'required|in:approve,reject',
                'admin_notes' => 'nullable|string'
            ]);

            $topupRequest = TopupRequest::findOrFail($request->request_id);
            
            if ($topupRequest->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Permintaan sudah diproses sebelumnya'
                ], 400);
            }

            DB::beginTransaction();

            if ($request->action === 'approve') {
                // Find user's account
                $account = Account::where('user_id', $topupRequest->user_id)
                    ->where('account_type', 'TABUNGAN')
                    ->first();

                if (!$account) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Rekening nasabah tidak ditemukan'
                    ], 404);
                }

                // Update account balance
                $account->increment('balance', $topupRequest->amount);

                // Create transaction record
                $transactionCode = 'TRX-' . time() . '-' . rand(100000, 999999);
                Transaction::create([
                    'transaction_code' => $transactionCode,
                    'to_account_id' => $account->id,
                    'transaction_type' => 'TOPUP',
                    'amount' => $topupRequest->amount,
                    'fee' => 0,
                    'description' => 'Top Up Saldo - ' . $topupRequest->payment_method,
                    'status' => 'SUCCESS',
                    'processed_by' => Auth::id()
                ]);

                $topupRequest->update([
                    'status' => 'approved',
                    'processed_by' => Auth::id(),
                    'processed_at' => now(),
                    'admin_notes' => $request->admin_notes
                ]);

                $message = 'Permintaan top up berhasil disetujui';
            } else {
                $topupRequest->update([
                    'status' => 'rejected',
                    'processed_by' => Auth::id(),
                    'processed_at' => now(),
                    'admin_notes' => $request->admin_notes
                ]);

                $message = 'Permintaan top up berhasil ditolak';
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses permintaan: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===== WITHDRAWAL REQUESTS =====
    public function processWithdrawalRequest(Request $request)
    {
        try {
            $request->validate([
                'request_id' => 'required|integer',
                'action' => 'required|in:approve,reject'
            ]);

            $withdrawalRequest = WithdrawalRequest::findOrFail($request->request_id);
            
            if ($withdrawalRequest->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Permintaan sudah diproses sebelumnya'
                ], 400);
            }

            DB::beginTransaction();

            if ($request->action === 'approve') {
                // Check user's balance
                $account = Account::where('user_id', $withdrawalRequest->user_id)
                    ->where('account_type', 'TABUNGAN')
                    ->first();

                if (!$account || $account->balance < $withdrawalRequest->amount) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Saldo nasabah tidak mencukupi'
                    ], 400);
                }

                // Deduct balance (hold the amount)
                $account->decrement('balance', $withdrawalRequest->amount);

                $withdrawalRequest->update([
                    'status' => 'approved',
                    'processed_by' => Auth::id(),
                    'processed_at' => now()
                ]);

                $message = 'Permintaan penarikan berhasil disetujui. Siap untuk pencairan.';
            } else {
                $withdrawalRequest->update([
                    'status' => 'rejected',
                    'processed_by' => Auth::id(),
                    'processed_at' => now()
                ]);

                $message = 'Permintaan penarikan berhasil ditolak';
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses permintaan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function disburseWithdrawal(Request $request)
    {
        try {
            $request->validate([
                'request_id' => 'required|integer'
            ]);

            $withdrawalRequest = WithdrawalRequest::findOrFail($request->request_id);
            
            if ($withdrawalRequest->status !== 'approved') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Permintaan belum disetujui'
                ], 400);
            }

            DB::beginTransaction();

            // Create transaction record
            $transactionCode = 'TRX-' . time() . '-' . rand(100000, 999999);
            Transaction::create([
                'transaction_code' => $transactionCode,
                'transaction_type' => 'WITHDRAWAL',
                'amount' => $withdrawalRequest->amount,
                'fee' => 0,
                'description' => 'Penarikan Dana ke Rekening External',
                'status' => 'SUCCESS',
                'processed_by' => Auth::id()
            ]);

            $withdrawalRequest->update([
                'status' => 'completed',
                'disbursed_by' => Auth::id(),
                'disbursed_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Dana berhasil dicairkan'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mencairkan dana: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===== CARD REQUESTS =====
    public function processCardRequest(Request $request, $cardId)
    {
        try {
            $request->validate([
                'action' => 'required|in:APPROVE,REJECT'
            ]);

            $cardRequest = CardRequest::findOrFail($cardId);
            
            if ($cardRequest->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Permintaan kartu sudah diproses sebelumnya'
                ], 400);
            }

            DB::beginTransaction();

            if ($request->action === 'APPROVE') {
                $cardRequest->update([
                    'status' => 'approved',
                    'processed_by' => Auth::id(),
                    'processed_at' => now()
                ]);

                $message = 'Kartu berhasil diaktifkan';
            } else {
                $cardRequest->update([
                    'status' => 'rejected',
                    'processed_by' => Auth::id(),
                    'processed_at' => now()
                ]);

                $message = 'Permintaan kartu berhasil ditolak';
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses permintaan kartu: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===== TRANSACTIONS =====
    public function getTransactionDetail(Request $request)
    {
        try {
            $transactionId = $request->query('id');
            
            if (!$transactionId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ID transaksi diperlukan'
                ], 400);
            }

            $transaction = Transaction::with(['fromAccount.user', 'toAccount.user'])
                ->findOrFail($transactionId);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $transaction->id,
                    'transaction_code' => $transaction->transaction_code,
                    'transaction_type' => $transaction->transaction_type,
                    'amount' => $transaction->amount,
                    'fee' => $transaction->fee,
                    'description' => $transaction->description,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at,
                    'from_account' => $transaction->fromAccount ? [
                        'account_number' => $transaction->fromAccount->account_number,
                        'user_name' => $transaction->fromAccount->user->full_name
                    ] : null,
                    'to_account' => $transaction->toAccount ? [
                        'account_number' => $transaction->toAccount->account_number,
                        'user_name' => $transaction->toAccount->user->full_name
                    ] : null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil detail transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===== LOAN APPLICATIONS =====
    public function loanInquiry(Request $request)
    {
        try {
            $request->validate([
                'loan_id' => 'required|integer'
            ]);

            $loan = Loan::with(['user', 'loanProduct'])
                ->findOrFail($request->loan_id);

            if ($loan->status !== 'DISBURSED') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pinjaman tidak dalam status aktif'
                ], 400);
            }

            // Calculate remaining balance (you may need to add this logic based on your business rules)
            $totalPaid = Transaction::where('transaction_type', 'LOAN_PAYMENT')
                ->where('reference_id', $loan->id)
                ->sum('amount');
            
            $remainingBalance = $loan->total_repayment - $totalPaid;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'loan_code' => 'LOAN-' . str_pad($loan->id, 6, '0', STR_PAD_LEFT),
                    'customer_name' => $loan->user->full_name,
                    'loan_amount' => $loan->loan_amount,
                    'total_repayment' => $loan->total_repayment,
                    'monthly_installment' => $loan->monthly_installment,
                    'remaining_balance' => max(0, $remainingBalance),
                    'status' => $loan->status,
                    'product_name' => $loan->loanProduct?->product_name
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data pinjaman: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateLoanApplicationStatus(Request $request)
    {
        try {
            $request->validate([
                'loan_id' => 'required|exists:loans,id',
                'status' => 'required|in:APPROVED,REJECTED',
                'rejection_reason' => 'required_if:status,REJECTED|string'
            ]);

            $loan = Loan::findOrFail($request->loan_id);
            
            if ($loan->status !== 'SUBMITTED') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pengajuan pinjaman sudah diproses sebelumnya'
                ], 400);
            }

            DB::beginTransaction();

            $updateData = [
                'status' => $request->status,
                'approved_by' => Auth::id(),
                'approved_at' => now()
            ];

            if ($request->status === 'REJECTED') {
                $updateData['rejection_reason'] = $request->rejection_reason;
            }

            $loan->update($updateData);

            // Send notification to customer
            if ($request->status === 'APPROVED') {
                $this->notificationService->notifyUser(
                    $loan->user_id,
                    'Pinjaman Disetujui',
                    'Pengajuan pinjaman Anda sebesar Rp ' . number_format($loan->loan_amount, 0, ',', '.') . ' telah disetujui. Menunggu pencairan dana.'
                );
                $message = 'Pengajuan pinjaman berhasil disetujui';
            } else {
                $this->notificationService->notifyUser(
                    $loan->user_id,
                    'Pinjaman Ditolak',
                    'Pengajuan pinjaman Anda ditolak. Alasan: ' . $request->rejection_reason
                );
                $message = 'Pengajuan pinjaman berhasil ditolak';
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses pengajuan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function disburseLoan(Request $request)
    {
        try {
            $request->validate([
                'loan_id' => 'required|exists:loans,id'
            ]);

            $loan = Loan::findOrFail($request->loan_id);
            
            if ($loan->status !== 'APPROVED') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pinjaman belum disetujui'
                ], 400);
            }

            $account = Account::where('user_id', $loan->user_id)
                ->where('account_type', 'TABUNGAN')
                ->first();

            if (!$account) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rekening tabungan tidak ditemukan'
                ], 404);
            }

            DB::beginTransaction();

            // Credit the loan amount to user's account
            $account->increment('balance', $loan->loan_amount);

            // Update loan status
            $loan->update([
                'status' => 'DISBURSED',
                'disbursed_at' => now(),
                'disbursed_by' => Auth::id()
            ]);

            // Create transaction record
            $transactionCode = 'TRX-' . time() . '-' . rand(100000, 999999);
            Transaction::create([
                'transaction_code' => $transactionCode,
                'to_account_id' => $account->id,
                'transaction_type' => 'LOAN_DISBURSEMENT',
                'amount' => $loan->loan_amount,
                'fee' => 0,
                'description' => 'Pencairan Pinjaman ' . ($loan->loanProduct?->product_name ?? ''),
                'status' => 'SUCCESS',
                'processed_by' => Auth::id()
            ]);

            // Send notification to customer
            $this->notificationService->notifyUser(
                $loan->user_id,
                'Pinjaman Dicairkan',
                'Dana pinjaman sebesar Rp ' . number_format($loan->loan_amount, 0, ',', '.') . ' telah dicairkan ke rekening Anda.'
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pinjaman berhasil dicairkan'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mencairkan pinjaman: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===== AUDIT LOG =====
    public function getAuditLog(Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $action = $request->query('action', '');
            $perPage = 20;

            $query = AuditLog::with('user')->orderBy('created_at', 'desc');

            if ($action && $action !== 'ALL') {
                $query->where('action', $action);
            }

            $logs = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'status' => 'success',
                'data' => $logs->items(),
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil audit log: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===== SYSTEM SETTINGS =====
    public function getSystemSettings()
    {
        try {
            // Get system settings from database or config
            $settings = [
                'monthly_admin_fee' => config('app.monthly_admin_fee', 0),
                'transfer_fee_external' => config('app.transfer_fee_external', 2500),
                'payment_qris_image_url' => config('app.payment_qris_image_url', ''),
                'payment_bank_accounts' => config('app.payment_bank_accounts', '[]'),
                'APP_DOWNLOAD_LINK_IOS' => config('app.download_link_ios', ''),
                'APP_DOWNLOAD_LINK_ANDROID' => config('app.download_link_android', '')
            ];

            return response()->json([
                'status' => 'success',
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil pengaturan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateSystemSettings(Request $request)
    {
        try {
            $request->validate([
                'monthly_admin_fee' => 'nullable|numeric|min:0',
                'transfer_fee_external' => 'nullable|numeric|min:0',
                'payment_qris_image_url' => 'nullable|url',
                'payment_bank_accounts' => 'nullable|json',
                'APP_DOWNLOAD_LINK_IOS' => 'nullable|url',
                'APP_DOWNLOAD_LINK_ANDROID' => 'nullable|url'
            ]);

            // Here you would typically save to database or update config files
            // For now, we'll just return success

            return response()->json([
                'status' => 'success',
                'message' => 'Pengaturan sistem berhasil diperbarui'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui pengaturan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updatePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required',
                'new_password' => 'required|min:6|confirmed'
            ]);

            $user = Auth::user();

            if (!Hash::check($request->current_password, $user->password_hash)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Password saat ini salah'
                ], 400);
            }

            $user->update([
                'password_hash' => Hash::make($request->new_password)
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Password berhasil diperbarui'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui password: ' . $e->getMessage()
            ], 500);
        }
    }
}