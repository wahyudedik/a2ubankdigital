<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Account;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function daily(Request $request): JsonResponse
    {
        $date = $request->input('date', today()->toDateString());

        $transactions = Transaction::whereDate('created_at', $date)
            ->selectRaw('
                transaction_type,
                COUNT(*) as count,
                SUM(amount) as total_amount
            ')
            ->groupBy('transaction_type')
            ->get();

        $summary = Transaction::whereDate('created_at', $date)
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(amount) as total_amount,
                SUM(fee) as total_fees
            ')
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'date' => $date,
                'summary' => $summary,
                'by_type' => $transactions
            ]
        ]);
    }

    public function accountBalance(Request $request): JsonResponse
    {
        $accountType = $request->input('account_type');

        $query = Account::with('user:id,full_name,bank_id')
            ->where('status', 'ACTIVE');

        if ($accountType) {
            $query->where('account_type', $accountType);
        }

        $accounts = $query->orderBy('balance', 'desc')->get();

        $summary = $query->selectRaw('
            account_type,
            COUNT(*) as count,
            SUM(balance) as total_balance
        ')
        ->groupBy('account_type')
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'accounts' => $accounts,
                'summary' => $summary
            ]
        ]);
    }

    public function npl(Request $request): JsonResponse
    {
        // NPL = Non-Performing Loan (overdue installments)
        $overdueInstallments = LoanInstallment::with(['loan.user'])
            ->where('status', 'PENDING')
            ->where('due_date', '<', now())
            ->get();

        $totalOutstanding = Loan::whereIn('status', ['DISBURSED', 'ACTIVE'])
            ->sum('loan_amount');

        $overdueAmount = $overdueInstallments->sum('total_amount');
        $nplRatio = $totalOutstanding > 0 ? ($overdueAmount / $totalOutstanding) * 100 : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'npl_ratio' => round($nplRatio, 2),
                'total_outstanding' => $totalOutstanding,
                'overdue_amount' => $overdueAmount,
                'overdue_installments' => $overdueInstallments
            ]
        ]);
    }

    public function profitLoss(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        // Income from loan interest
        $loanInterest = LoanInstallment::where('status', 'PAID')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('interest_amount');

        // Income from deposit interest (expense for bank)
        $depositInterest = Account::where('account_type', 'DEPOSITO')
            ->where('status', 'CLOSED')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->with('depositProduct')
            ->get()
            ->sum(function($deposit) {
                $principal = $deposit->balance;
                $rate = $deposit->depositProduct->interest_rate_pa / 100;
                $months = $deposit->depositProduct->tenor_months;
                return $principal * $rate * ($months / 12);
            });

        // Transaction fees
        $transactionFees = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'SUCCESS')
            ->sum('fee');

        $totalIncome = $loanInterest + $transactionFees;
        $totalExpense = $depositInterest;
        $netProfit = $totalIncome - $totalExpense;

        return response()->json([
            'status' => 'success',
            'data' => [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'income' => [
                    'loan_interest' => $loanInterest,
                    'transaction_fees' => $transactionFees,
                    'total' => $totalIncome
                ],
                'expense' => [
                    'deposit_interest' => $depositInterest,
                    'total' => $totalExpense
                ],
                'net_profit' => $netProfit
            ]
        ]);
    }

    public function customerGrowth(Request $request): JsonResponse
    {
        $months = $request->input('months', 12);

        $growth = User::where('role_id', 9)
            ->where('created_at', '>=', now()->subMonths($months))
            ->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                COUNT(*) as new_customers
            ')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $growth
        ]);
    }
}
