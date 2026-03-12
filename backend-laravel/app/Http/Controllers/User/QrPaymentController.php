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

class QrPaymentController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Generate QR code for payment
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'sometimes|numeric|min:1000|max:10000000'
        ]);

        try {
            // Get user's account
            $account = Account::where('user_id', Auth::id())
                ->where('account_type', 'TABUNGAN')
                ->first();

            if (!$account) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rekening tidak ditemukan.'
                ], 404);
            }

            // Create QR payload
            $payload = [
                'iss' => 'a2ubankdigital.my.id',
                'acc' => $account->account_number,
                'name' => Auth::user()->full_name,
                'amt' => $request->amount ?? 0,
                'exp' => now()->addMinutes(30)->timestamp
            ];

            // Generate QR code (simplified - in production use proper QR library)
            $qrData = base64_encode(json_encode($payload));
            $qrBase64 = $this->generateQrCodeBase64($qrData);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'qr_base64' => $qrBase64,
                    'qr_data' => $qrData,
                    'expires_at' => now()->addMinutes(30)->toDateTimeString(),
                    'account_number' => $account->account_number,
                    'recipient_name' => Auth::user()->full_name,
                    'amount' => $request->amount ?? 0
                ]
            ]);

        } catch (\Exception $e) {
            $this->logService->logError('QR generation failed', $e);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat QR Code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Scan QR and get payment info
     */
    public function scanInfo(Request $request): JsonResponse
    {
        $request->validate([
            'qr_data' => 'required|string'
        ]);

        try {
            // Decode QR data
            $payload = json_decode(base64_decode($request->qr_data), true);

            if (!$payload || !isset($payload['acc']) || !isset($payload['name'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'QR Code tidak valid.'
                ], 400);
            }

            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < now()->timestamp) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'QR Code sudah kadaluarsa.'
                ], 400);
            }

            // Verify account exists
            $account = Account::where('account_number', $payload['acc'])
                ->where('status', 'ACTIVE')
                ->first();

            if (!$account) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rekening tujuan tidak ditemukan.'
                ], 404);
            }

            // Check not paying to self
            if ($account->user_id == Auth::id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak dapat membayar ke rekening sendiri.'
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'account_number' => $payload['acc'],
                    'recipient_name' => $payload['name'],
                    'amount' => $payload['amt'] ?? 0,
                    'is_fixed_amount' => isset($payload['amt']) && $payload['amt'] > 0
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'QR Code tidak valid atau rusak.'
            ], 400);
        }
    }

    /**
     * Execute QR payment
     */
    public function pay(Request $request): JsonResponse
    {
        $request->validate([
            'qr_data' => 'required|string',
            'amount' => 'required|numeric|min:1000|max:10000000',
            'description' => 'sometimes|string|max:255'
        ]);

        try {
            // Decode and validate QR
            $payload = json_decode(base64_decode($request->qr_data), true);

            if (!$payload || !isset($payload['acc'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'QR Code tidak valid.'
                ], 400);
            }

            // Check if amount is fixed in QR
            if (isset($payload['amt']) && $payload['amt'] > 0 && $payload['amt'] != $request->amount) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Jumlah pembayaran harus sesuai dengan QR Code.'
                ], 400);
            }

            DB::beginTransaction();

            // Get source account
            $sourceAccount = Account::where('user_id', Auth::id())
                ->where('account_type', 'TABUNGAN')
                ->lockForUpdate()
                ->first();

            if (!$sourceAccount || $sourceAccount->balance < $request->amount) {
                throw new \Exception('Saldo tidak mencukupi.');
            }

            // Get destination account
            $destinationAccount = Account::where('account_number', $payload['acc'])
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->first();

            if (!$destinationAccount) {
                throw new \Exception('Rekening tujuan tidak ditemukan.');
            }

            // Create transaction
            $transaction = Transaction::create([
                'transaction_code' => 'QR-' . time() . '-' . rand(100000, 999999),
                'from_account_id' => $sourceAccount->id,
                'to_account_id' => $destinationAccount->id,
                'transaction_type' => 'TRANSFER_QR',
                'amount' => $request->amount,
                'fee' => 0,
                'description' => $request->description ?? 'Pembayaran QR ke ' . $payload['name'],
                'status' => 'SUCCESS'
            ]);

            // Update balances
            $sourceAccount->decrement('balance', $request->amount);
            $destinationAccount->increment('balance', $request->amount);

            // Log transaction
            $this->logService->logTransaction('QR_PAYMENT', $transaction->id, [
                'qr_account' => $payload['acc'],
                'amount' => $request->amount
            ]);

            // Send notifications
            $this->notificationService->notifyUser(
                Auth::id(),
                'Pembayaran QR Berhasil',
                "Pembayaran QR ke {$payload['name']} sebesar " . number_format($request->amount, 0, ',', '.') . " berhasil."
            );

            $this->notificationService->notifyUser(
                $destinationAccount->user_id,
                'Pembayaran QR Diterima',
                "Anda menerima pembayaran QR sebesar " . number_format($request->amount, 0, ',', '.') . " dari " . Auth::user()->full_name . "."
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran QR berhasil.',
                'data' => $transaction
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->logService->logError('QR payment failed', $e);

            return response()->json([
                'status' => 'error',
                'message' => 'Pembayaran gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate QR code base64 (simplified)
     */
    private function generateQrCodeBase64(string $data): string
    {
        // This is a simplified implementation
        // In production, use a proper QR code library like chillerlan/php-qrcode
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==';
    }
}