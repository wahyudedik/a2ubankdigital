<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 15);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $type = $request->input('type');

        // Get all user account IDs
        $userAccountIds = Account::where('user_id', $user->id)->pluck('id')->toArray();

        if (empty($userAccountIds)) {
            return response()->json([
                'status' => 'success',
                'pagination' => [
                    'current_page' => 1,
                    'total_pages' => 0,
                    'total_records' => 0,
                    'has_more' => false
                ],
                'data' => []
            ]);
        }

        // Build query
        $query = Transaction::query()
            ->leftJoin('accounts as from_acc', 'transactions.from_account_id', '=', 'from_acc.id')
            ->leftJoin('users as from_user', 'from_acc.user_id', '=', 'from_user.id')
            ->leftJoin('loan_installments as li', 'transactions.id', '=', 'li.transaction_id')
            ->leftJoin('loans as l', 'li.loan_id', '=', 'l.id')
            ->where(function($q) use ($userAccountIds, $user) {
                $q->whereIn('transactions.from_account_id', $userAccountIds)
                  ->orWhereIn('transactions.to_account_id', $userAccountIds)
                  ->orWhere('l.user_id', $user->id);
            });

        // Apply filters
        if ($startDate && $endDate) {
            $query->whereBetween(DB::raw('DATE(transactions.created_at)'), [$startDate, $endDate]);
        }

        if ($type) {
            $query->where('transactions.transaction_type', $type);
        }

        // Get total count
        $totalRecords = $query->distinct('transactions.id')->count('transactions.id');
        $totalPages = ceil($totalRecords / $limit);

        // Get paginated data
        $transactions = $query
            ->select([
                'transactions.id',
                'transactions.transaction_type',
                'transactions.amount',
                'transactions.status',
                'transactions.created_at',
                DB::raw("IF(transactions.to_account_id IN (" . implode(',', $userAccountIds) . ") OR transactions.transaction_type LIKE 'LOAN_DISBURSEMENT%', 'KREDIT', 'DEBIT') as flow"),
                DB::raw("(CASE
                    WHEN transactions.to_account_id IN (" . implode(',', $userAccountIds) . ") AND transactions.transaction_type IN ('TRANSFER_INTERNAL', 'TRANSFER_QR') THEN CONCAT('Transfer dari ', from_user.full_name)
                    WHEN transactions.transaction_type = 'LOAN_PAYMENT' THEN 'Pembayaran Angsuran'
                    ELSE transactions.description
                END) as description")
            ])
            ->groupBy('transactions.id')
            ->orderBy('transactions.created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'pagination' => [
                'current_page' => (int)$page,
                'total_pages' => (int)$totalPages,
                'total_records' => (int)$totalRecords,
                'has_more' => $page < $totalPages
            ],
            'data' => $transactions
        ]);
    }

    public function show($id): JsonResponse
    {
        $user = Auth::user();
        $userAccountIds = Account::where('user_id', $user->id)->pluck('id')->toArray();

        $transaction = Transaction::with(['fromAccount', 'toAccount'])
            ->where(function($q) use ($userAccountIds) {
                $q->whereIn('from_account_id', $userAccountIds)
                  ->orWhereIn('to_account_id', $userAccountIds);
            })
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $transaction
        ]);
    }

    public function internalTransferInquiry(Request $request): JsonResponse
    {
        $request->validate([
            'destination_account_number' => 'required|string'
        ]);

        $destinationAccount = Account::with('user')
            ->where('account_number', $request->destination_account_number)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$destinationAccount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nomor rekening tujuan tidak ditemukan atau tidak aktif.'
            ], 404);
        }

        // Check not transferring to self
        if ($destinationAccount->user_id == Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak dapat mentransfer ke rekening Anda sendiri.'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'account_number' => $destinationAccount->account_number,
                'recipient_name' => $destinationAccount->user->full_name
            ]
        ]);
    }

    public function internalTransferExecute(Request $request): JsonResponse
    {
        $request->validate([
            'destination_account_number' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'description' => 'sometimes|string'
        ]);

        $user = Auth::user();
        $amount = (float)$request->amount;

        DB::beginTransaction();
        try {
            // Get source account (user's savings account)
            $sourceAccount = Account::where('user_id', $user->id)
                ->where('account_type', 'TABUNGAN')
                ->lockForUpdate()
                ->first();

            if (!$sourceAccount || $sourceAccount->balance < $amount) {
                throw new \Exception('Saldo tidak mencukupi.');
            }

            // Get destination account
            $destinationAccount = Account::where('account_number', $request->destination_account_number)
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->first();

            if (!$destinationAccount) {
                throw new \Exception('Rekening tujuan tidak ditemukan.');
            }

            // Create transaction
            $transaction = Transaction::create([
                'transaction_code' => 'TRX-' . time() . '-' . rand(100000, 999999),
                'from_account_id' => $sourceAccount->id,
                'to_account_id' => $destinationAccount->id,
                'transaction_type' => 'TRANSFER_INTERNAL',
                'amount' => $amount,
                'fee' => 0,
                'description' => $request->description ?? 'Transfer Internal',
                'status' => 'SUCCESS'
            ]);

            // Update balances
            $sourceAccount->decrement('balance', $amount);
            $destinationAccount->increment('balance', $amount);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Transfer berhasil.',
                'data' => $transaction
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Transfer gagal: ' . $e->getMessage()
            ], 500);
        }
    }
}
