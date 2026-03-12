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
        // Total customers
        $totalCustomers = User::where('role_id', 9)->count();
        $activeCustomers = User::where('role_id', 9)->where('status', 'ACTIVE')->count();

        // Total accounts and balances
        $savingsData = Account::where('account_type', 'TABUNGAN')
            ->selectRaw('COUNT(*) as count, SUM(balance) as total_balance')
            ->first();

        $depositData = Account::where('account_type', 'DEPOSITO')
            ->where('status', 'ACTIVE')
            ->selectRaw('COUNT(*) as count, SUM(balance) as total_balance')
            ->first();

        // Loan statistics
        $loanStats = Loan::selectRaw('
            COUNT(*) as total_loans,
            SUM(CASE WHEN status = "SUBMITTED" THEN 1 ELSE 0 END) as pending_loans,
            SUM(CASE WHEN status IN ("DISBURSED", "ACTIVE") THEN 1 ELSE 0 END) as active_loans,
            SUM(CASE WHEN status IN ("DISBURSED", "ACTIVE") THEN loan_amount ELSE 0 END) as outstanding_amount
        ')->first();

        // Today's transactions
        $todayTransactions = Transaction::whereDate('created_at', today())
            ->selectRaw('
                COUNT(*) as count,
                SUM(amount) as total_amount
            ')
            ->first();

        // Monthly growth
        $monthlyGrowth = User::where('role_id', 9)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'customers' => [
                    'total' => $totalCustomers,
                    'active' => $activeCustomers,
                    'monthly_growth' => $monthlyGrowth
                ],
                'savings' => [
                    'total_accounts' => $savingsData->count ?? 0,
                    'total_balance' => $savingsData->total_balance ?? 0
                ],
                'deposits' => [
                    'total_accounts' => $depositData->count ?? 0,
                    'total_balance' => $depositData->total_balance ?? 0
                ],
                'loans' => [
                    'total' => $loanStats->total_loans ?? 0,
                    'pending' => $loanStats->pending_loans ?? 0,
                    'active' => $loanStats->active_loans ?? 0,
                    'outstanding_amount' => $loanStats->outstanding_amount ?? 0
                ],
                'transactions_today' => [
                    'count' => $todayTransactions->count ?? 0,
                    'total_amount' => $todayTransactions->total_amount ?? 0
                ]
            ]
        ]);
    }
}
