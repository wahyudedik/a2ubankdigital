<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Transaction;
use App\Models\Account;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LoanController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    public function index(Request $request): JsonResponse
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $status = $request->input('status');

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

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:APPROVED,REJECTED',
            'rejection_reason' => 'required_if:status,REJECTED'
        ]);

        $loan = Loan::findOrFail($id);

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

        // Send notification to customer
        if ($request->status === 'APPROVED') {
            $this->notificationService->notifyUser(
                $loan->user_id,
                'Pinjaman Disetujui',
                'Pengajuan pinjaman Anda sebesar Rp ' . number_format($loan->loan_amount, 0, ',', '.') . ' telah disetujui. Menunggu pencairan dana.'
            );
        } elseif ($request->status === 'REJECTED') {
            $this->notificationService->notifyUser(
                $loan->user_id,
                'Pinjaman Ditolak',
                'Pengajuan pinjaman Anda ditolak. Alasan: ' . ($request->rejection_reason ?? 'Tidak memenuhi syarat.')
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Status pinjaman berhasil diperbarui.',
            'data' => $loan->fresh()
        ]);
    }

    public function disburse(Request $request, $id): JsonResponse
    {
        $loan = Loan::findOrFail($id);

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
                'description' => 'Pencairan Pinjaman ' . $loan->loanProduct->product_name,
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

            // Send notification to customer
            $this->notificationService->notifyUser(
                $loan->user_id,
                'Pinjaman Dicairkan',
                'Dana pinjaman sebesar Rp ' . number_format($loan->loan_amount, 0, ',', '.') . ' telah dicairkan ke rekening Anda.'
            );

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
        $principalPerInstallment = $loan->loan_amount / $loan->tenor;
        $remainingPrincipal = $loan->loan_amount;

        for ($i = 1; $i <= $loan->tenor; $i++) {
            $dueDate = now()->addMonths($i);
            
            if ($i === $loan->tenor) {
                $principal = $remainingPrincipal;
            } else {
                $principal = $principalPerInstallment;
            }

            $interest = $installmentAmount - $principal;

            LoanInstallment::create([
                'loan_id' => $loan->id,
                'installment_number' => $i,
                'due_date' => $dueDate,
                'principal_amount' => $principal,
                'interest_amount' => $interest,
                'total_amount' => $installmentAmount,
                'status' => 'PENDING'
            ]);

            $remainingPrincipal -= $principal;
        }
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
