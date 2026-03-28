<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardRequest;
use App\Models\Card;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CardRequestController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get card requests
     */
    public function index(Request $request): JsonResponse
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $status = $request->input('status');

        $query = CardRequest::with('user');

        if ($status) {
            $query->where('status', $status);
        }

        $totalRecords = $query->count();
        $requests = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $requests,
            'pagination' => [
                'current_page' => (int)$page,
                'total_records' => (int)$totalRecords
            ]
        ]);
    }

    /**
     * Get card request details
     */
    public function show($id): JsonResponse
    {
        $request = CardRequest::with(['user', 'processedBy'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $request
        ]);
    }

    /**
     * Process card request (approve/reject)
     */
    public function process(Request $request, $id): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:APPROVE,REJECT',
            'rejection_reason' => 'required_if:action,REJECT'
        ]);

        $cardRequest = CardRequest::findOrFail($id);

        if ($cardRequest->status !== 'PENDING') {
            return response()->json([
                'status' => 'error',
                'message' => 'Permintaan kartu sudah diproses sebelumnya.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            if ($request->action === 'APPROVE') {
                // Approve and create card
                $cardRequest->update([
                    'status' => 'APPROVED',
                    'processed_by' => Auth::id(),
                    'processed_at' => now()
                ]);

                // Generate card number
                $cardNumber = $this->generateCardNumber();
                $maskedCardNumber = substr($cardNumber, 0, 4) . '-****-****-' . substr($cardNumber, -4);

                // Get user's savings account
                $userAccount = \App\Models\Account::where('user_id', $cardRequest->user_id)
                    ->where('account_type', 'TABUNGAN')
                    ->first();

                // Create card
                Card::create([
                    'user_id' => $cardRequest->user_id,
                    'account_id' => $userAccount ? $userAccount->id : null,
                    'card_number_masked' => $maskedCardNumber,
                    'card_type' => strtolower($cardRequest->card_type),
                    'status' => 'active',
                    'daily_limit' => $cardRequest->card_type === 'DEBIT' ? 5000000 : 10000000,
                    'requested_at' => $cardRequest->created_at,
                    'activated_at' => now(),
                    'expiry_date' => now()->addYears(3)->toDateString()
                ]);

                // Notify customer
                $this->notificationService->notifyUser(
                    $cardRequest->user_id,
                    'Permintaan Kartu Disetujui',
                    'Permintaan kartu ' . $cardRequest->card_type . ' Anda telah disetujui dan kartu akan segera dikirim.'
                );

                $message = 'Permintaan kartu berhasil disetujui dan kartu telah dibuat.';
            } else {
                // Reject
                $cardRequest->update([
                    'status' => 'REJECTED',
                    'processed_by' => Auth::id(),
                    'processed_at' => now(),
                    'rejection_reason' => $request->rejection_reason
                ]);

                // Notify customer
                $this->notificationService->notifyUser(
                    $cardRequest->user_id,
                    'Permintaan Kartu Ditolak',
                    'Permintaan kartu ' . $cardRequest->card_type . ' Anda ditolak. Alasan: ' . $request->rejection_reason
                );

                $message = 'Permintaan kartu berhasil ditolak.';
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $cardRequest->fresh(['user', 'processedBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses permintaan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate card number
     */
    private function generateCardNumber(): string
    {
        do {
            // Generate 16-digit card number starting with 4 (Visa-like)
            $cardNumber = '4' . str_pad(rand(0, 999999999999999), 15, '0', STR_PAD_LEFT);
        } while (Card::where('card_number_masked', 'LIKE', substr($cardNumber, 0, 4) . '%' . substr($cardNumber, -4))->exists());

        return $cardNumber;
    }
}