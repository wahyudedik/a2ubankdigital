<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\DepositProduct;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DepositController extends Controller
{
    public function index(): JsonResponse
    {
        $deposits = Account::where('user_id', Auth::id())
            ->where('account_type', 'DEPOSITO')
            ->with('depositProduct')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $deposits
        ]);
    }

    public function show($id): JsonResponse
    {
        $deposit = Account::where('user_id', Auth::id())
            ->where('account_type', 'DEPOSITO')
            ->with('depositProduct')
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $deposit
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:deposit_products,id',
            'amount' => 'required|numeric|min:0'
        ]);

        $product = DepositProduct::where('id', $request->product_id)
            ->where('is_active', true)
            ->first();

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk deposito tidak valid atau tidak aktif.'
            ], 400);
        }

        if ($request->amount < $product->min_amount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jumlah penempatan di bawah minimum yang disyaratkan produk.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Get savings account
            $savingsAccount = Account::where('user_id', Auth::id())
                ->where('account_type', 'TABUNGAN')
                ->lockForUpdate()
                ->first();

            if (!$savingsAccount || $savingsAccount->balance < $request->amount) {
                throw new \Exception('Saldo tabungan tidak mencukupi untuk penempatan deposito.');
            }

            // Deduct from savings
            $savingsAccount->decrement('balance', $request->amount);

            // Calculate maturity date
            $maturityDate = now()->addMonths($product->tenor_months);

            // Create deposit account
            $depositAccount = Account::create([
                'user_id' => Auth::id(),
                'account_type' => 'DEPOSITO',
                'balance' => $request->amount,
                'status' => 'ACTIVE',
                'deposit_product_id' => $product->id,
                'maturity_date' => $maturityDate
            ]);

            // Create transaction
            Transaction::create([
                'transaction_code' => 'TRX-' . time() . '-' . rand(100000, 999999),
                'from_account_id' => $savingsAccount->id,
                'to_account_id' => $depositAccount->id,
                'transaction_type' => 'PEMBUKAAN_DEPOSITO',
                'amount' => $request->amount,
                'fee' => 0,
                'description' => 'Pembukaan ' . $product->product_name,
                'status' => 'SUCCESS'
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pembukaan deposito berhasil.',
                'data' => $depositAccount->fresh('depositProduct')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuka deposito: ' . $e->getMessage()
            ], 500);
        }
    }

    public function disburse($id): JsonResponse
    {
        $deposit = Account::where('user_id', Auth::id())
            ->where('account_type', 'DEPOSITO')
            ->with('depositProduct')
            ->findOrFail($id);

        if ($deposit->status !== 'ACTIVE') {
            return response()->json([
                'status' => 'error',
                'message' => 'Deposito tidak dalam status aktif.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Get savings account
            $savingsAccount = Account::where('user_id', Auth::id())
                ->where('account_type', 'TABUNGAN')
                ->lockForUpdate()
                ->first();

            if (!$savingsAccount) {
                throw new \Exception('Rekening tabungan tidak ditemukan.');
            }

            // Calculate interest
            $principal = $deposit->balance;
            $interestRate = $deposit->depositProduct->interest_rate_pa / 100;
            $months = $deposit->depositProduct->tenor_months;
            $interest = $principal * $interestRate * ($months / 12);
            $totalAmount = $principal + $interest;

            // Transfer to savings
            $savingsAccount->increment('balance', $totalAmount);

            // Close deposit account
            $deposit->update([
                'balance' => 0,
                'status' => 'CLOSED'
            ]);

            // Create transaction
            Transaction::create([
                'transaction_code' => 'TRX-' . time() . '-' . rand(100000, 999999),
                'from_account_id' => $deposit->id,
                'to_account_id' => $savingsAccount->id,
                'transaction_type' => 'PENCAIRAN_DEPOSITO',
                'amount' => $totalAmount,
                'fee' => 0,
                'description' => 'Pencairan Deposito (Pokok: ' . number_format($principal, 0) . ', Bunga: ' . number_format($interest, 0) . ')',
                'status' => 'SUCCESS'
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pencairan deposito berhasil.',
                'data' => [
                    'principal' => $principal,
                    'interest' => $interest,
                    'total_amount' => $totalAmount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mencairkan deposito: ' . $e->getMessage()
            ], 500);
        }
    }

    public function products(): JsonResponse
    {
        $products = DepositProduct::where('is_active', true)
            ->orderBy('min_amount')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }
}
