<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Account;
use App\Models\Loan;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        // KPI Stats
        $totalCustomerFunds = (float) (Account::where('status', 'ACTIVE')->sum('balance') ?? 0);
        
        $outstandingLoanPortfolio = (float) (Loan::whereIn('status', ['DISBURSED', 'ACTIVE'])
            ->sum('loan_amount') ?? 0);

        $feeRevenueMonthly = (float) (Transaction::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', 'SUCCESS')
            ->sum('fee') ?? 0);

        $newCustomersMonthly = User::where('role_id', 9)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Pending Tasks
        $pendingTopups = DB::table('topup_requests')->where('status', 'pending')->count();
        $pendingWithdrawals = DB::table('withdrawal_requests')->where('status', 'pending')->count();
        $pendingLoans = Loan::where('status', 'SUBMITTED')->count();
        $pendingLoanDisbursements = Loan::where('status', 'APPROVED')->count();
        $pendingWithdrawalDisbursements = DB::table('withdrawal_requests')->where('status', 'APPROVED')->count();

        // Recent Activities (last 10 transactions)
        $recentActivities = DB::table('transactions as t')
            ->leftJoin('accounts as from_acc', 't.from_account_id', '=', 'from_acc.id')
            ->leftJoin('users as from_user', 'from_acc.user_id', '=', 'from_user.id')
            ->leftJoin('accounts as to_acc', 't.to_account_id', '=', 'to_acc.id')
            ->leftJoin('users as to_user', 'to_acc.user_id', '=', 'to_user.id')
            ->select([
                't.id',
                't.transaction_code',
                't.transaction_type',
                't.amount',
                't.description',
                't.status',
                't.created_at',
                DB::raw('COALESCE(from_user.full_name, to_user.full_name, "System") as full_name'),
            ])
            ->where('t.status', 'SUCCESS')
            ->orderBy('t.created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'kpi' => [
                    'fee_revenue_monthly' => $feeRevenueMonthly,
                    'total_customer_funds' => $totalCustomerFunds,
                    'outstanding_loan_portfolio' => $outstandingLoanPortfolio,
                    'new_customers_monthly' => $newCustomersMonthly,
                ],
                'tasks' => [
                    'pendingTopups' => $pendingTopups,
                    'pendingWithdrawals' => $pendingWithdrawals,
                    'pendingLoans' => $pendingLoans,
                    'pendingLoanDisbursements' => $pendingLoanDisbursements,
                    'pendingWithdrawalDisbursements' => $pendingWithdrawalDisbursements,
                ],
                'recentActivities' => $recentActivities,
            ]
        ]);
    }
}
