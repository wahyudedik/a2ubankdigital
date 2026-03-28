<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\ExternalBank;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExternalTransferController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * External transfer inquiry
     */
    public function inquiry(Request $request): JsonResponse
    {
        $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_number' => 'required|string|max:20',
            'to_bank_code' => 'required|string|max:10',
            'amount' => 'required|numeric|min:1|max:50000000'
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

        // Verify destination bank
        $destinationBank = ExternalBank::where('bank_code', $request->to_bank_code)
            ->where('is_active', true)
            ->first();

        if (!$destinationBank) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bank tujuan tidak ditemukan atau tidak aktif.'
            ], 404);
        }

        // Calculate fees
        $transferFee = 6500; // External transfer fee from settings
        $totalAmount = $request->amount + $transferFee;

        // Check balance
        if ($fromAccount->balance < $totalAmount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Saldo tidak mencukupi. Dibutuhkan Rp ' . number_format($totalAmount, 2, ',', '.') . ' (termasuk biaya admin Rp ' . number_format($transferFee, 2, ',', '.') . ').'
            ], 400);
        }

        // Simulate account validation (in real implementation, this would call external API)
        $destinationAccountInfo = [
            'account_number' => $request->to_account_number,
            'account_name' => 'NAMA PENERIMA', // In real implementation, get from external API
            'bank_name' => $destinationBank->bank_name,
            'bank_code' => $destinationBank->bank_code
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Inquiry berhasil.',
            'data' => [
                'from_account' => [
                    'account_number' => $fromAccount->account_number,
                    'balance' => $fromAccount->balance
                ],
                'to_account' => $destinationAccountInfo,
                'transfer_details' => [
                    'amount' => $request->amount,
                    'fee' => $transferFee,
                    'total_debit' => $totalAmount
                ]
            ]
        ]);
    }

    /**
     * Execute external transfer
     */
    public function execute(Request $request): JsonResponse
    {
        $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_number' => 'required|string|max:20',
            'to_bank_code' => 'required|string|max:10',
            'amount' => 'required|numeric|min:1|max:50000000',
            'description' => 'sometimes|string|max:255'
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

            // Verify destination bank
            $destinationBank = ExternalBank::where('bank_code', $request->to_bank_code)
                ->where('is_active', true)
                ->first();

            if (!$destinationBank) {
                throw new \Exception("Bank tujuan tidak ditemukan atau tidak aktif.");
            }

            // Calculate fees
            $transferFee = 6500;
            $totalAmount = $request->amount + $transferFee;

            // Check balance
            if ($fromAccount->balance < $totalAmount) {
                throw new \Exception("Saldo tidak mencukupi untuk transfer ini.");
            }

            // Deduct from source account
            $fromAccount->decrement('balance', $totalAmount);

            // Create transaction record
            $description = $request->description ?? 'Transfer ke ' . $destinationBank->bank_name . ' - ' . $request->to_account_number;
            
            $transaction = Transaction::create([
                'transaction_code' => 'TRX-' . time() . '-' . rand(100000, 999999),
                'from_account_id' => $fromAccount->id,
                'to_account_id' => null, // External transfer
                'transaction_type' => 'TRANSFER_EXTERNAL',
                'amount' => $request->amount,
                'fee' => $transferFee,
                'description' => $description,
                'status' => 'SUCCESS',
                'reference_number' => 'EXT-' . time() . '-' . rand(100000, 999999),
                'external_bank_code' => $request->to_bank_code,
                'external_account_number' => $request->to_account_number
            ]);

            // Log the transfer
            $this->logService->logAudit('EXTERNAL_TRANSFER_EXECUTED', 'transactions', $transaction->id, [], [
                'amount' => $request->amount,
                'fee' => $transferFee,
                'to_bank_code' => $request->to_bank_code,
                'to_account_number' => $request->to_account_number
            ]);

            // Create notification
            $this->notificationService->notifyUser(
                $user->id,
                'Transfer Eksternal Berhasil',
                'Transfer sebesar Rp ' . number_format($request->amount, 2, ',', '.') . ' ke ' . $destinationBank->bank_name . ' berhasil diproses.'
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Transfer eksternal berhasil diproses.',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'transaction_code' => $transaction->transaction_code,
                    'reference_number' => $transaction->reference_number,
                    'amount' => $request->amount,
                    'fee' => $transferFee,
                    'total_debit' => $totalAmount,
                    'remaining_balance' => $fromAccount->fresh()->balance
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Transfer gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get interbank list
     */
    public function getInterbankList(): JsonResponse
    {
        $banks = ExternalBank::active()
            ->orderBy('bank_name')
            ->get(['id', 'bank_name', 'bank_code']);

        return response()->json([
            'status' => 'success',
            'data' => $banks
        ]);
    }

    /**
     * Get bank branches (mock implementation)
     */
    public function getBankBranches(Request $request): JsonResponse
    {
        $request->validate([
            'bank_code' => 'required|string|exists:external_banks,bank_code'
        ]);

        // Mock data - in real implementation, this would call external API
        $branches = [
            [
                'branch_code' => '001',
                'branch_name' => 'Kantor Pusat',
                'city' => 'Jakarta'
            ],
            [
                'branch_code' => '002', 
                'branch_name' => 'Cabang Surabaya',
                'city' => 'Surabaya'
            ],
            [
                'branch_code' => '003',
                'branch_name' => 'Cabang Bandung', 
                'city' => 'Bandung'
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $branches
        ]);
    }
}