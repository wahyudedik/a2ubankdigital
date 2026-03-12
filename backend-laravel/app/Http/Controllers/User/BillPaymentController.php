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
use Illuminate\Support\Facades\Http;

class BillPaymentController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get available billers
     */
    public function getBillers(): JsonResponse
    {
        // Static data for billers - in production this would come from external API
        $billers = [
            [
                'id' => 'PLN',
                'name' => 'PLN (Listrik)',
                'category' => 'ELECTRICITY',
                'fee' => 2500,
                'is_active' => true
            ],
            [
                'id' => 'PDAM',
                'name' => 'PDAM (Air)',
                'category' => 'WATER',
                'fee' => 2000,
                'is_active' => true
            ],
            [
                'id' => 'TELKOM',
                'name' => 'Telkom Indonesia',
                'category' => 'INTERNET',
                'fee' => 3000,
                'is_active' => true
            ],
            [
                'id' => 'INDIHOME',
                'name' => 'IndiHome',
                'category' => 'INTERNET',
                'fee' => 3000,
                'is_active' => true
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $billers
        ]);
    }

    /**
     * Bill payment inquiry
     */
    public function inquiry(Request $request): JsonResponse
    {
        $request->validate([
            'biller_id' => 'required|string',
            'customer_number' => 'required|string'
        ]);

        try {
            // Simulate external API call for bill inquiry
            // In production, this would call actual biller API
            $billInfo = $this->simulateBillInquiry($request->biller_id, $request->customer_number);

            if (!$billInfo) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nomor pelanggan tidak ditemukan atau tagihan tidak tersedia.'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $billInfo
            ]);

        } catch (\Exception $e) {
            $this->logService->logError('Bill inquiry failed', $e, [
                'biller_id' => $request->biller_id,
                'customer_number' => $request->customer_number
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal melakukan inquiry tagihan.'
            ], 500);
        }
    }

    /**
     * Execute bill payment
     */
    public function execute(Request $request): JsonResponse
    {
        $request->validate([
            'biller_id' => 'required|string',
            'customer_number' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'admin_fee' => 'required|numeric|min:0'
        ]);

        $totalAmount = $request->amount + $request->admin_fee;

        DB::beginTransaction();
        try {
            // Get user's account
            $account = Account::where('user_id', Auth::id())
                ->where('account_type', 'TABUNGAN')
                ->lockForUpdate()
                ->first();

            if (!$account || $account->balance < $totalAmount) {
                throw new \Exception('Saldo tidak mencukupi untuk pembayaran tagihan.');
            }

            // Create transaction
            $transaction = Transaction::create([
                'transaction_code' => 'BILL-' . time() . '-' . rand(100000, 999999),
                'from_account_id' => $account->id,
                'transaction_type' => 'BILL_PAYMENT',
                'amount' => $request->amount,
                'fee' => $request->admin_fee,
                'description' => "Pembayaran {$request->biller_id} - {$request->customer_number}",
                'status' => 'PROCESSING'
            ]);

            // Deduct balance
            $account->decrement('balance', $totalAmount);

            // Simulate payment to biller (in production, call actual API)
            $paymentResult = $this->simulatePaymentExecution($request->biller_id, $request->customer_number, $request->amount);

            if ($paymentResult['success']) {
                $transaction->update(['status' => 'SUCCESS']);
                
                // Log successful payment
                $this->logService->logTransaction('BILL_PAYMENT', $transaction->id, [
                    'biller_id' => $request->biller_id,
                    'customer_number' => $request->customer_number,
                    'amount' => $request->amount,
                    'fee' => $request->admin_fee
                ]);

                // Send notification
                $this->notificationService->notifyUser(
                    Auth::id(),
                    'Pembayaran Berhasil',
                    "Pembayaran tagihan {$request->biller_id} sebesar " . number_format($request->amount, 0, ',', '.') . " berhasil diproses."
                );

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Pembayaran tagihan berhasil.',
                    'data' => [
                        'transaction' => $transaction,
                        'receipt_number' => $paymentResult['receipt_number']
                    ]
                ]);
            } else {
                // Payment failed, refund balance
                $account->increment('balance', $totalAmount);
                $transaction->update(['status' => 'FAILED']);

                throw new \Exception($paymentResult['message'] ?? 'Pembayaran gagal diproses.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->logService->logError('Bill payment execution failed', $e, [
                'biller_id' => $request->biller_id,
                'customer_number' => $request->customer_number
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Pembayaran gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment history
     */
    public function history(Request $request): JsonResponse
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 15);

        $userAccountIds = Account::where('user_id', Auth::id())->pluck('id');

        $query = Transaction::whereIn('from_account_id', $userAccountIds)
            ->where('transaction_type', 'BILL_PAYMENT');

        $totalRecords = $query->count();
        $payments = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $payments,
            'pagination' => [
                'current_page' => (int)$page,
                'total_records' => (int)$totalRecords
            ]
        ]);
    }

    /**
     * Simulate bill inquiry (replace with actual API call)
     */
    private function simulateBillInquiry(string $billerId, string $customerNumber): ?array
    {
        // Simulate different responses based on biller
        $bills = [
            'PLN' => [
                'customer_name' => 'John Doe',
                'period' => date('Y-m'),
                'due_date' => date('Y-m-d', strtotime('+7 days')),
                'amount' => rand(100000, 500000),
                'admin_fee' => 2500
            ],
            'PDAM' => [
                'customer_name' => 'Jane Smith',
                'period' => date('Y-m'),
                'due_date' => date('Y-m-d', strtotime('+10 days')),
                'amount' => rand(50000, 200000),
                'admin_fee' => 2000
            ]
        ];

        if (!isset($bills[$billerId])) {
            return null;
        }

        return array_merge($bills[$billerId], [
            'biller_id' => $billerId,
            'customer_number' => $customerNumber
        ]);
    }

    /**
     * Simulate payment execution (replace with actual API call)
     */
    private function simulatePaymentExecution(string $billerId, string $customerNumber, float $amount): array
    {
        // Simulate 95% success rate
        $success = rand(1, 100) <= 95;

        if ($success) {
            return [
                'success' => true,
                'receipt_number' => 'RCP-' . time() . '-' . rand(1000, 9999),
                'message' => 'Pembayaran berhasil diproses.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Pembayaran gagal diproses oleh sistem biller.'
            ];
        }
    }
}