<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\LoanInstallment;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    public function index(): JsonResponse
    {
        $loans = Loan::where('user_id', Auth::id())
            ->with(['loanProduct', 'installments'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $loans
        ]);
    }

    public function show($id): JsonResponse
    {
        $loan = Loan::where('user_id', Auth::id())
            ->with(['loanProduct', 'installments'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $loan
        ]);
    }

    public function apply(Request $request): JsonResponse
    {
        $request->validate([
            'loan_product_id' => 'required|exists:loan_products,id',
            'loan_amount' => 'required|numeric|min:0',
            'tenor' => 'required|integer|min:1',
            'purpose' => 'sometimes|string'
        ]);

        $loanProduct = LoanProduct::findOrFail($request->loan_product_id);

        // Validate amount range
        if ($request->loan_amount < $loanProduct->min_amount || $request->loan_amount > $loanProduct->max_amount) {
            return response()->json([
                'status' => 'error',
                'message' => "Jumlah pinjaman harus antara {$loanProduct->min_amount} dan {$loanProduct->max_amount}."
            ], 400);
        }

        // Calculate installment
        $interestRate = $loanProduct->interest_rate_pa / 100 / 12; // Monthly rate
        $monthlyInstallment = ($request->loan_amount * $interestRate * pow(1 + $interestRate, $request->tenor)) / 
                             (pow(1 + $interestRate, $request->tenor) - 1);
        $totalInterest = ($monthlyInstallment * $request->tenor) - $request->loan_amount;
        $totalRepayment = $request->loan_amount + $totalInterest;

        $loan = Loan::create([
            'user_id' => Auth::id(),
            'loan_product_id' => $request->loan_product_id,
            'loan_amount' => $request->loan_amount,
            'interest_rate_pa' => $loanProduct->interest_rate_pa,
            'tenor' => $request->tenor,
            'tenor_unit' => $loanProduct->tenor_unit,
            'monthly_installment' => $monthlyInstallment,
            'total_interest' => $totalInterest,
            'total_repayment' => $totalRepayment,
            'purpose' => $request->purpose,
            'status' => 'SUBMITTED'
        ]);

        // Notify customer
        $this->notificationService->notifyUser(
            Auth::id(),
            'Pengajuan Pinjaman Diterima',
            'Pengajuan pinjaman Anda sebesar Rp ' . number_format($request->loan_amount, 0, ',', '.') . ' telah diterima dan sedang diproses.'
        );

        // Notify admin staff
        $this->notificationService->notifyStaffByRole(
            [1, 2, 3], // Super Admin, Admin, Manager
            'Pengajuan Pinjaman Baru',
            'Pengajuan pinjaman baru dari ' . Auth::user()->full_name . ' sebesar Rp ' . number_format($request->loan_amount, 0, ',', '.') . ' menunggu persetujuan.'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan pinjaman berhasil disubmit.',
            'data' => $loan
        ], 201);
    }

    public function payInstallment(Request $request, $id): JsonResponse
    {
        $loan = Loan::where('user_id', Auth::id())->findOrFail($id);

        if ($loan->status !== 'DISBURSED' && $loan->status !== 'ACTIVE') {
            return response()->json([
                'status' => 'error',
                'message' => 'Pinjaman tidak dalam status aktif.'
            ], 400);
        }

        // Get next pending/overdue installment
        $installment = LoanInstallment::where('loan_id', $loan->id)
            ->whereIn('status', ['PENDING', 'OVERDUE'])
            ->orderBy('installment_number')
            ->first();

        if (!$installment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada cicilan yang perlu dibayar.'
            ], 400);
        }

        // Get user's account
        $account = Account::where('user_id', Auth::id())
            ->where('account_type', 'TABUNGAN')
            ->first();

        // Calculate total due including late fee
        $totalDue = $installment->total_amount + ($installment->late_fee ?? 0);

        if (!$account || $account->balance < $totalDue) {
            return response()->json([
                'status' => 'error',
                'message' => 'Saldo tidak mencukupi untuk membayar cicilan. Total yang harus dibayar: Rp ' . number_format($totalDue, 0, ',', '.')
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
                'description' => "Bayar Angsuran Pinjaman #{$loan->id} ke-{$installment->installment_number}",
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
                
                // Notify customer about loan completion
                $this->notificationService->notifyUser(
                    Auth::id(),
                    'Pinjaman Lunas',
                    'Selamat! Pinjaman Anda telah lunas. Terima kasih atas kepercayaan Anda.'
                );
            } else {
                $loan->update(['status' => 'ACTIVE']);
            }

            // Notify customer about payment
            $this->notificationService->notifyUser(
                Auth::id(),
                'Pembayaran Angsuran Berhasil',
                'Pembayaran angsuran ke-' . $installment->installment_number . ' sebesar Rp ' . number_format($totalDue, 0, ',', '.') . ' berhasil diproses.'
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran cicilan berhasil.',
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

    public function products(): JsonResponse
    {
        $products = LoanProduct::where('is_active', true)
            ->orderBy('min_amount')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }
}
