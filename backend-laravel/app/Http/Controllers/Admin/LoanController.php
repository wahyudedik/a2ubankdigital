<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LoanController extends Controller
{
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
}
