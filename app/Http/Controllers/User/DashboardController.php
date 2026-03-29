<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard summary
     * GET /api/user/dashboard
     */
    public function summary(Request $request)
    {
        $user = $request->user();

        // Get main savings account
        $account = $user->accounts()
            ->where('account_type', 'TABUNGAN')
            ->where('status', 'ACTIVE')
            ->first();

        if (!$account) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rekening tabungan tidak ditemukan.'
            ], 404);
        }

        // Get recent transactions (5 latest)
        $transactions = DB::table('transactions as t')
            ->leftJoin('accounts as from_acc', 't.from_account_id', '=', 'from_acc.id')
            ->leftJoin('users as from_user', 'from_acc.user_id', '=', 'from_user.id')
            ->select(
                't.transaction_code',
                't.transaction_type',
                't.amount',
                DB::raw("CASE 
                    WHEN t.to_account_id = {$account->id} AND t.transaction_type = 'TRANSFER_INTERNAL' 
                    THEN CONCAT('Transfer dari ', from_user.full_name)
                    ELSE t.description 
                END as description"),
                't.created_at',
                DB::raw("IF(t.to_account_id = " . intval($account->id) . ", 'KREDIT', 'DEBIT') as flow")
            )
            ->where(function ($query) use ($account) {
                $query->where('t.from_account_id', $account->id)
                    ->orWhere('t.to_account_id', $account->id);
            })
            ->orderBy('t.created_at', 'desc')
            ->limit(5)
            ->get();

        // Get weekly summary (last 7 days)
        $weeklySummary = $this->getWeeklySummary($account->id);

        // Get monthly summary
        $monthlySummary = DB::table('transactions')
            ->select(
                DB::raw('SUM(CASE WHEN to_account_id = ? THEN amount ELSE 0 END) as total_pemasukan'),
                DB::raw('SUM(CASE WHEN from_account_id = ? THEN amount ELSE 0 END) as total_pengeluaran')
            )
            ->where(function ($query) use ($account) {
                $query->where('from_account_id', $account->id)
                    ->orWhere('to_account_id', $account->id);
            })
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', 'SUCCESS')
            ->setBindings([$account->id, $account->id])
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'account_id' => $account->id,
                'account_number' => $account->account_number,
                'balance' => (float) $account->balance,
                'recent_transactions' => $transactions,
                'weekly_summary' => $weeklySummary,
                'monthly_summary' => [
                    'income' => (float) ($monthlySummary->total_pemasukan ?? 0),
                    'expense' => (float) ($monthlySummary->total_pengeluaran ?? 0),
                ],
            ]
        ]);
    }

    /**
     * Get all user accounts
     * GET /api/user/accounts
     */
    public function getAllAccounts(Request $request)
    {
        $user = $request->user();
        $accounts = $user->accounts()->get();

        return response()->json([
            'status' => 'success',
            'data' => $accounts
        ]);
    }

    /**
     * Get account detail
     * GET /api/user/accounts/{id}
     */
    public function getAccountDetail(Request $request, $id)
    {
        $user = $request->user();
        $account = $user->accounts()->find($id);

        if (!$account) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rekening tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $account
        ]);
    }

    /**
     * Get weekly summary for chart
     */
    private function getWeeklySummary($accountId)
    {
        $weeklyData = DB::table('transactions')
            ->select(
                DB::raw('DATE(created_at) as transaction_date'),
                DB::raw('SUM(CASE WHEN to_account_id = ? THEN amount ELSE 0 END) as total_pemasukan'),
                DB::raw('SUM(CASE WHEN from_account_id = ? THEN amount ELSE 0 END) as total_pengeluaran')
            )
            ->where(function ($query) use ($accountId) {
                $query->where('from_account_id', $accountId)
                    ->orWhere('to_account_id', $accountId);
            })
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->where('status', 'SUCCESS')
            ->groupBy('transaction_date')
            ->orderBy('transaction_date', 'asc')
            ->setBindings([$accountId, $accountId])
            ->get()
            ->keyBy('transaction_date');

        // Prepare data for last 7 days
        $labels = [];
        $pemasukan = [];
        $pengeluaran = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayLabel = now()->subDays($i)->format('D');
            
            $labels[] = $dayLabel;
            $pemasukan[] = (float) ($weeklyData[$date]->total_pemasukan ?? 0);
            $pengeluaran[] = (float) ($weeklyData[$date]->total_pengeluaran ?? 0);
        }

        return [
            'labels' => $labels,
            'pemasukan' => $pemasukan,
            'pengeluaran' => $pengeluaran,
        ];
    }
}
