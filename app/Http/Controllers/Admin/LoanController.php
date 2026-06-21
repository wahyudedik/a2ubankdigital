<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Transaction;
use App\Models\Account;
use App\Services\NotificationService;
use App\Services\EmailService;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LoanController extends Controller
{
    protected $notificationService;
    protected $logService;
    protected $emailService;

    public function __construct(NotificationService $notificationService, LogService $logService, EmailService $emailService)
    {
        $this->notificationService = $notificationService;
        $this->logService = $logService;
        $this->emailService = $emailService;
    }
    public function index(Request $request): JsonResponse
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $status = $request->input('status');

        if ($page < 1 || $limit < 1 || $limit > 100) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter pagination tidak valid. Halaman minimal 1, limit antara 1 dan 100.'
            ], 422);
        }

        $query = Loan::with(['user', 'loanProduct']);

        if ($status) {
            $query->where('status', $status);
        }

        $totalRecords = $query->count();
        $totalPages = ceil($totalRecords / $limit);

        $loans = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $loans,
            'pagination' => [
                'current_page' => (int)$page,
                'total_pages' => (int)$totalPages,
                'total_records' => (int)$totalRecords
            ]
        ]);
    }

    public function show($id): JsonResponse
    {
        $loan = Loan::with(['user', 'loanProduct', 'installments'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $loan
        ]);
    }

    public function updateStatus(Request $request, $id = null): JsonResponse
    {
        // Support both URL parameter and request body
        $loanId = $id ?? $request->input('loan_id');
        
        if (!$loanId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Loan ID is required.'
            ], 400);
        }

        $request->validate([
            'status' => 'required|in:APPROVED,REJECTED',
            'rejection_reason' => 'nullable|required_if:status,REJECTED|string'
        ]);

        $loan = Loan::with('user')->findOrFail($loanId);

        if ($loan->status !== 'SUBMITTED') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya pinjaman dengan status SUBMITTED yang dapat diproses.'
            ], 400);
        }

        $loan->update([
            'status' => $request->status,
            'rejection_reason' => $request->rejection_reason,
            'approved_at' => now(),
            'approved_by' => Auth::id()
        ]);

        // Audit log
        $this->logService->logAudit(
            $request->status === 'APPROVED' ? 'APPROVE_LOAN' : 'REJECT_LOAN',
            'loans', $loan->id,
            ['status' => 'SUBMITTED'],
            ['status' => $request->status, 'approved_by' => Auth::id()]
        );

        // Send notification to customer
        if ($request->status === 'APPROVED') {
            $this->notificationService->notifyUser(
                $loan->user_id,
                'Pinjaman Disetujui',
                'Pengajuan pinjaman Anda sebesar Rp ' . number_format($loan->loan_amount, 0, ',', '.') . ' telah disetujui. Menunggu pencairan dana.'
            );
            try {
                $this->emailService->send(
                    $loan->user->email,
                    $loan->user->full_name,
                    'Pinjaman Anda Disetujui',
                    'loan_approved',
                    [
                        'full_name' => $loan->user->full_name,
                        'loan_amount' => 'Rp ' . number_format($loan->loan_amount, 0, ',', '.'),
                        'preheader' => 'Pengajuan pinjaman Anda telah disetujui.'
                    ]
                );
            } catch (\Exception $e) {}
        } elseif ($request->status === 'REJECTED') {
            $this->notificationService->notifyUser(
                $loan->user_id,
                'Pinjaman Ditolak',
                'Pengajuan pinjaman Anda ditolak. Alasan: ' . ($request->rejection_reason ?? 'Tidak memenuhi syarat.')
            );
            try {
                $this->emailService->send(
                    $loan->user->email,
                    $loan->user->full_name,
                    'Pinjaman Anda Ditolak',
                    'loan_rejected',
                    [
                        'full_name' => $loan->user->full_name,
                        'loan_amount' => 'Rp ' . number_format($loan->loan_amount, 0, ',', '.'),
                        'rejection_reason' => $request->rejection_reason ?? 'Tidak memenuhi syarat.',
                        'preheader' => 'Pengajuan pinjaman Anda ditolak.'
                    ]
                );
            } catch (\Exception $e) {}
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Status pinjaman berhasil diperbarui.',
            'data' => $loan->fresh()
        ]);
    }

    public function disburse(Request $request, $id = null): JsonResponse
    {
        // Support both URL parameter and request body
        $loanId = $id ?? $request->input('loan_id');
        
        if (!$loanId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Loan ID is required.'
            ], 400);
        }

        $loan = Loan::with('user')->findOrFail($loanId);

        if ($loan->status !== 'APPROVED') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya pinjaman yang sudah disetujui yang dapat dicairkan.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Get user's account
            $account = Account::where('user_id', $loan->user_id)
                ->where('account_type', 'TABUNGAN')
                ->first();

            if (!$account) {
                throw new \Exception('Rekening nasabah tidak ditemukan.');
            }

            // Create disbursement transaction
            $transaction = Transaction::create([
                'transaction_code' => 'TRX-' . time() . '-' . rand(100000, 999999),
                'to_account_id' => $account->id,
                'transaction_type' => 'LOAN_DISBURSEMENT',
                'amount' => $loan->loan_amount,
                'fee' => 0,
                'description' => 'Pencairan Pinjaman ' . ($loan->loanProduct?->product_name ?? 'N/A'),
                'status' => 'SUCCESS'
            ]);

            // Update account balance
            $account->increment('balance', $loan->loan_amount);

            // Update loan status
            $loan->update([
                'status' => 'DISBURSED',
                'disbursed_at' => now(),
                'disbursed_by' => Auth::id()
            ]);

            // Generate installments
            $this->generateInstallments($loan);

            // Audit log
            $this->logService->logAudit('DISBURSE_LOAN', 'loans', $loan->id, 
                ['status' => 'APPROVED'], 
                ['status' => 'DISBURSED', 'disbursed_by' => Auth::id(), 'amount' => $loan->loan_amount]
            );

            // Send notification to customer
            $this->notificationService->notifyUser(
                $loan->user_id,
                'Pinjaman Dicairkan',
                'Dana pinjaman sebesar Rp ' . number_format($loan->loan_amount, 0, ',', '.') . ' telah dicairkan ke rekening Anda.'
            );
            try {
                $this->emailService->send(
                    $loan->user->email,
                    $loan->user->full_name,
                    'Dana Pinjaman Telah Dicairkan',
                    'loan_disbursed',
                    [
                        'full_name' => $loan->user->full_name,
                        'loan_amount' => 'Rp ' . number_format($loan->loan_amount, 0, ',', '.'),
                        'preheader' => 'Dana pinjaman Anda telah dicairkan ke rekening.'
                    ]
                );
            } catch (\Exception $e) {}

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pinjaman berhasil dicairkan.',
                'data' => $loan->fresh(['installments'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mencairkan pinjaman: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateInstallments(Loan $loan): void
    {
        $installmentAmount = $loan->monthly_installment;
        $remainingPrincipal = $loan->loan_amount;
        $tenorUnit = $loan->tenor_unit ?? 'BULAN';

        // Rate per period disesuaikan dengan tenor_unit
        if ($tenorUnit === 'MINGGU') {
            $periodRate = $loan->interest_rate_pa / 100 / 52;
        } else {
            $periodRate = $loan->interest_rate_pa / 100 / 12;
        }

        for ($i = 1; $i <= $loan->tenor; $i++) {
            $dueDate = $tenorUnit === 'MINGGU'
                ? now()->addWeeks($i)
                : now()->addMonths($i);

            // Hitung interest berdasarkan sisa pokok (amortisasi)
            $interest = round($remainingPrincipal * $periodRate, 2);
            $principal = round($installmentAmount - $interest, 2);

            // Angsuran terakhir: lunasi sisa pokok agar tidak ada selisih pembulatan
            if ($i === $loan->tenor) {
                $principal = round($remainingPrincipal, 2);
                $interest = round($installmentAmount - $principal, 2);
                if ($interest < 0) {
                    $interest = 0;
                }
            }

            LoanInstallment::create([
                'loan_id' => $loan->id,
                'installment_number' => $i,
                'due_date' => $dueDate,
                'principal_amount' => $principal,
                'interest_amount' => $interest,
                'total_amount' => $principal + $interest,
                'status' => 'PENDING'
            ]);

            $remainingPrincipal = round($remainingPrincipal - $principal, 2);
        }
    }

    public function destroy($id): JsonResponse
    {
        $loan = Loan::findOrFail($id);

        $deletableStatuses = ['SUBMITTED', 'REJECTED', 'COMPLETED', 'CLOSED'];
        $blockedStatuses = ['DISBURSED', 'ACTIVE', 'OVERDUE'];

        if (in_array($loan->status, $blockedStatuses)) {
            $hasOutstanding = $loan->installments()->whereIn('status', ['PENDING', 'OVERDUE'])->exists();
            if ($hasOutstanding) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pinjaman dengan status ' . $loan->status . ' tidak dapat dihapus karena belum lunas.'
                ], 400);
            }
        } elseif (!in_array($loan->status, $deletableStatuses)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Status pinjaman tidak valid untuk dihapus.'
            ], 400);
        }

        DB::transaction(function () use ($loan) {
            $loan->installments()->delete();
            $loan->delete();
        });

        $this->logService->logAudit(
            'DELETE_LOAN',
            'loans', $id,
            ['status' => $loan->status],
            []
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Pinjaman berhasil dihapus.'
        ]);
    }

    public function forcePayInstallment(Request $request, $id): JsonResponse
    {
        $request->validate([
            'installment_id' => 'required|exists:loan_installments,id'
        ]);

        $loan = Loan::findOrFail($id);
        $installment = LoanInstallment::where('id', $request->installment_id)
            ->where('loan_id', $loan->id)
            ->firstOrFail();

        if ($installment->status === 'PAID') {
            return response()->json([
                'status' => 'error',
                'message' => 'Angsuran ini sudah dibayar.'
            ], 400);
        }

        // Get customer's account
        $account = Account::where('user_id', $loan->user_id)
            ->where('account_type', 'TABUNGAN')
            ->first();

        if (!$account) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rekening nasabah tidak ditemukan.'
            ], 400);
        }

        $totalDue = $installment->total_amount + ($installment->late_fee ?? 0);

        if ($account->balance < $totalDue) {
            return response()->json([
                'status' => 'error',
                'message' => 'Saldo nasabah tidak mencukupi untuk membayar angsuran ini.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Create transaction
            $transaction = Transaction::create([
                'transaction_code' => 'TRX-' . time() . '-' . rand(100000, 999999),
                'from_account_id' => $account->id,
                'transaction_type' => 'LOAN_PAYMENT',
                'amount' => $totalDue,
                'fee' => 0,
                'description' => "Pembayaran Paksa Angsuran #{$installment->installment_number} - Pinjaman #{$loan->id}",
                'status' => 'SUCCESS'
            ]);

            // Update account balance
            $account->decrement('balance', $totalDue);

            // Update installment
            $installment->update([
                'status' => 'PAID',
                'paid_amount' => $totalDue,
                'paid_at' => now()
            ]);

            // Check if all installments are paid
            $remainingInstallments = LoanInstallment::where('loan_id', $loan->id)
                ->whereIn('status', ['PENDING', 'OVERDUE'])
                ->count();

            if ($remainingInstallments === 0) {
                $loan->update(['status' => 'COMPLETED']);
            } else {
                $loan->update(['status' => 'ACTIVE']);
            }

            // Send notification to customer
            $this->notificationService->notifyUser(
                $loan->user_id,
                'Angsuran Dibayar',
                'Angsuran ke-' . $installment->installment_number . ' sebesar Rp ' . number_format($totalDue, 0, ',', '.') . ' telah dibayar dari saldo Anda.'
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Angsuran berhasil dibayar.',
                'data' => [
                    'transaction' => $transaction,
                    'installment' => $installment->fresh(),
                    'remaining_installments' => $remainingInstallments
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
}
