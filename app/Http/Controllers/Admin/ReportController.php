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

        $creditTotal = (float) Transaction::whereDate('created_at', $date)
            ->where('status', 'SUCCESS')
            ->whereNotNull('to_account_id')
            ->sum('amount');

        $debitTotal = (float) Transaction::whereDate('created_at', $date)
            ->where('status', 'SUCCESS')
            ->whereNotNull('from_account_id')
            ->sum('amount');

        $details = Transaction::whereDate('created_at', $date)
            ->where('status', 'SUCCESS')
            ->selectRaw('transaction_type, COUNT(*) as count, SUM(amount) as amount')
            ->groupBy('transaction_type')
            ->get()
            ->keyBy('transaction_type')
            ->map(fn($item) => ['count' => $item->count, 'amount' => (float) $item->amount]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'date' => $date,
                'summary' => [
                    'total_credit' => $creditTotal,
                    'total_debit' => $debitTotal,
                ],
                'details' => $details,
            ]
        ]);
    }

    public function accountBalance(Request $request): JsonResponse
    {
        $data = Account::where('status', 'ACTIVE')
            ->selectRaw('account_type, COUNT(*) as number_of_accounts, SUM(balance) as total_balance')
            ->groupBy('account_type')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function npl(Request $request): JsonResponse
    {
        $daysOverdue = $request->input('days_overdue', 30);

        $overdueData = DB::table('loan_installments as li')
            ->join('loans as l', 'li.loan_id', '=', 'l.id')
            ->join('users as u', 'l.user_id', '=', 'u.id')
            ->leftJoin('customer_profiles as cp', 'u.id', '=', 'cp.user_id')
            ->where('li.status', 'PENDING')
            ->whereRaw('DATEDIFF(NOW(), li.due_date) >= ?', [$daysOverdue])
            ->select([
                'u.full_name',
                'u.phone_number',
                'li.total_amount as installment_amount',
                DB::raw('DATEDIFF(NOW(), li.due_date) as overdue_days'),
            ])
            ->orderBy('overdue_days', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $overdueData
        ]);
    }

    public function profitLoss(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $revenueFromInterest = (float) (LoanInstallment::where('status', 'PAID')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('interest_amount') ?? 0);

        $revenueFromFees = (float) (Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'SUCCESS')
            ->sum('fee') ?? 0);

        $totalExpense = 0; // Simplified - deposit interest expense

        return response()->json([
            'status' => 'success',
            'data' => [
                'revenue_from_interest' => $revenueFromInterest,
                'revenue_from_fees' => $revenueFromFees,
                'total_expense' => $totalExpense,
                'net_profit' => $revenueFromInterest + $revenueFromFees - $totalExpense,
            ]
        ]);
    }

    public function customerGrowth(Request $request): JsonResponse
    {
        $growth = User::where('role_id', 9)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('
                DATE(created_at) as registration_date,
                COUNT(*) as new_customers
            ')
            ->groupBy('registration_date')
            ->orderBy('registration_date')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $growth
        ]);
    }
}
