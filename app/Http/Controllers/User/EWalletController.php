<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EWalletController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * E-wallet top-up inquiry
     */
    public function topupInquiry(Request $request): JsonResponse
    {
        $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'ewallet_provider' => 'required|in:GOPAY,OVO,DANA,SHOPEEPAY,LINKAJA',
            'phone_number' => 'required|string|regex:/^08[0-9]{8,11}$/',
            'amount' => 'required|numeric|min:10000|max:2000000'
        ]);

        $user = Auth::user();

        // Verify account ownership
        $fromAccount = Account::where('id', $request->from_account_id)
            ->where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$fromAccount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rekening sumber tidak ditemukan atau tidak aktif.'
            ], 404);
        }

        // Calculate fees based on provider and amount
        $fee = $this->calculateTopupFee($request->ewallet_provider, $request->amount);
        $totalAmount = $request->amount + $fee;

        // Check balance
        if ($fromAccount->balance < $totalAmount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Saldo tidak mencukupi. Dibutuhkan Rp ' . number_format($totalAmount, 2, ',', '.') . ' (termasuk biaya admin Rp ' . number_format($fee, 2, ',', '.') . ').'
            ], 400);
        }

        // Simulate phone number validation (in real implementation, call provider API)
        $customerInfo = [
            'phone_number' => $request->phone_number,
            'customer_name' => 'NAMA PELANGGAN', // From provider API
            'provider' => $request->ewallet_provider
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Inquiry berhasil.',
            'data' => [
                'from_account' => [
                    'account_number' => $fromAccount->account_number,
                    'balance' => $fromAccount->balance
                ],
                'topup_details' => [
                    'provider' => $request->ewallet_provider,
                    'phone_number' => $request->phone_number,
                    'customer_name' => $customerInfo['customer_name'],
                    'amount' => $request->amount,
                    'fee' => $fee,
                    'total_debit' => $totalAmount
                ]
            ]
        ]);
    }

    /**
     * Execute e-wallet top-up
     */
    public function topupExecute(Request $request): JsonResponse
    {
        $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'ewallet_provider' => 'required|in:GOPAY,OVO,DANA,SHOPEEPAY,LINKAJA',
            'phone_number' => 'required|string|regex:/^08[0-9]{8,11}$/',
            'amount' => 'required|numeric|min:10000|max:2000000'
        ]);

        $user = Auth::user();

        DB::beginTransaction();
        try {
            // Verify account ownership and lock
            $fromAccount = Account::where('id', $request->from_account_id)
                ->where('user_id', $user->id)
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->first();

            if (!$fromAccount) {
                throw new \Exception("Rekening sumber tidak ditemukan atau tidak aktif.");
            }

            // Calculate fees
            $fee = $this->calculateTopupFee($request->ewallet_provider, $request->amount);
            $totalAmount = $request->amount + $fee;

            // Check balance
            if ($fromAccount->balance < $totalAmount) {
                throw new \Exception("Saldo tidak mencukupi untuk top-up ini.");
            }

            // Deduct from source account
            $fromAccount->decrement('balance', $totalAmount);

            // Create transaction record
            $description = 'Top-up ' . $request->ewallet_provider . ' - ' . $request->phone_number;
            
            $transaction = Transaction::create([
                'transaction_code' => 'TRX-' . time() . '-' . rand(100000, 999999),
                'from_account_id' => $fromAccount->id,
                'to_account_id' => null,
                'transaction_type' => 'TOPUP_EWALLET',
                'amount' => $request->amount,
                'fee' => $fee,
                'description' => $description,
                'status' => 'SUCCESS',
                'external_ref_id' => 'EWALLET-' . time() . '-' . rand(100000, 999999),
                'external_sn' => 'SN' . time() . rand(1000, 9999) // Serial number from provider
            ]);

            // Log the top-up
            $this->logService->logAudit('EWALLET_TOPUP_EXECUTED', 'transactions', $transaction->id, [], [
                'provider' => $request->ewallet_provider,
                'phone_number' => $request->phone_number,
                'amount' => $request->amount,
                'fee' => $fee
            ]);

            // Create notification
            $this->notificationService->notifyUser(
                $user->id,
                'Top-up E-Wallet Berhasil',
                'Top-up ' . $request->ewallet_provider . ' sebesar Rp ' . number_format($request->amount, 2, ',', '.') . ' ke nomor ' . $request->phone_number . ' berhasil diproses.'
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Top-up e-wallet berhasil diproses.',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'transaction_code' => $transaction->transaction_code,
                    'external_ref_id' => $transaction->external_ref_id,
                    'serial_number' => $transaction->external_sn,
                    'provider' => $request->ewallet_provider,
                    'phone_number' => $request->phone_number,
                    'amount' => $request->amount,
                    'fee' => $fee,
                    'total_debit' => $totalAmount,
                    'remaining_balance' => $fromAccount->fresh()->balance
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Top-up gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get e-wallet providers
     */
    public function getProviders(): JsonResponse
    {
        $providers = [
            [
                'code' => 'GOPAY',
                'name' => 'GoPay',
                'min_amount' => 10000,
                'max_amount' => 2000000,
                'fee_percentage' => 0,
                'fixed_fee' => 2500
            ],
            [
                'code' => 'OVO',
                'name' => 'OVO',
                'min_amount' => 10000,
                'max_amount' => 2000000,
                'fee_percentage' => 0,
                'fixed_fee' => 2500
            ],
            [
                'code' => 'DANA',
                'name' => 'DANA',
                'min_amount' => 10000,
                'max_amount' => 2000000,
                'fee_percentage' => 0,
                'fixed_fee' => 2500
            ],
            [
                'code' => 'SHOPEEPAY',
                'name' => 'ShopeePay',
                'min_amount' => 10000,
                'max_amount' => 2000000,
                'fee_percentage' => 0,
                'fixed_fee' => 2500
            ],
            [
                'code' => 'LINKAJA',
                'name' => 'LinkAja',
                'min_amount' => 10000,
                'max_amount' => 2000000,
                'fee_percentage' => 0,
                'fixed_fee' => 2500
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $providers
        ]);
    }

    /**
     * Calculate top-up fee based on provider and amount
     */
    private function calculateTopupFee(string $provider, float $amount): float
    {
        // Standard fee for all e-wallet providers
        $baseFee = 2500;
        
        // Additional fees based on provider (if any)
        $providerFees = [
            'GOPAY' => 0,
            'OVO' => 0,
            'DANA' => 0,
            'SHOPEEPAY' => 0,
            'LINKAJA' => 0
        ];

        return $baseFee + ($providerFees[$provider] ?? 0);
    }
}