<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\CardRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CardController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get user's cards
     */
    public function index(): JsonResponse
    {
        $cards = Card::where('user_id', Auth::id())
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $cards
        ]);
    }

    /**
     * Get card details
     */
    public function show($id): JsonResponse
    {
        $card = Card::where('user_id', Auth::id())
            ->with('user')
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $card
        ]);
    }

    /**
     * Request new card
     */
    public function requestCard(Request $request): JsonResponse
    {
        $request->validate([
            'card_type' => 'required|in:DEBIT,CREDIT',
            'delivery_address' => 'required|string',
            'reason' => 'sometimes|string'
        ]);

        // Check if user already has pending request
        $pendingRequest = CardRequest::where('user_id', Auth::id())
            ->where('status', 'PENDING')
            ->first();

        if ($pendingRequest) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda masih memiliki permintaan kartu yang sedang diproses.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $cardRequest = CardRequest::create([
                'user_id' => Auth::id(),
                'card_type' => $request->card_type,
                'delivery_address' => $request->delivery_address,
                'reason' => $request->reason,
                'status' => 'PENDING'
            ]);

            // Notify admin staff
            $this->notificationService->notifyStaffByRole(
                [1, 2, 3], // Super Admin, Admin, Manager
                'Permintaan Kartu Baru',
                'Nasabah ' . Auth::user()->full_name . ' mengajukan permintaan kartu ' . $request->card_type . ' baru.'
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Permintaan kartu berhasil diajukan.',
                'data' => $cardRequest
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengajukan permintaan kartu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set card limit
     */
    public function setLimit(Request $request, $id): JsonResponse
    {
        $request->validate([
            'daily_limit' => 'required|integer|min:0|max:50000000'
        ]);

        $card = Card::where('user_id', Auth::id())
            ->findOrFail($id);

        $card->update([
            'daily_limit' => (int) $request->daily_limit
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Limit kartu berhasil diperbarui.',
            'data' => $card
        ]);
    }

    /**
     * Reveal full card number after PIN verification
     */
    public function revealNumber(Request $request, $id): JsonResponse
    {
        $request->validate([
            'transaction_pin' => 'required|string'
        ]);

        $user = Auth::user();

        // Cek apakah user sudah set PIN
        if (!$user->pin_hash) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda belum mengatur PIN transaksi. Silakan buat PIN terlebih dahulu di menu Ubah PIN.'
            ], 403);
        }

        if (!Hash::check($request->transaction_pin, $user->pin_hash)) {
            return response()->json([
                'status' => 'error',
                'message' => 'PIN transaksi tidak valid.'
            ], 403);
        }

        $card = Card::where('user_id', $user->id)->findOrFail($id);

        // Dekripsi nomor kartu penuh
        $fullNumber = $card->getFullCardNumber();

        if (!$fullNumber) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nomor kartu penuh tidak tersedia. Hubungi Customer Service.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'card_number' => $fullNumber,
                'card_number_masked' => $card->card_number_masked,
            ]
        ]);
    }

    /**
     * Update card status (block/unblock/close)
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:active,blocked,closed'
        ]);

        $card = Card::where('user_id', Auth::id())
            ->findOrFail($id);

        if ($card->status === 'closed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Kartu telah ditutup secara permanen dan tidak dapat diubah statusnya.'
            ], 422);
        }

        $card->update([
            'status' => $request->status
        ]);

        $statusMap = [
            'active'  => 'diaktifkan',
            'blocked' => 'diblokir',
            'closed'  => 'ditutup secara permanen',
        ];
        $statusText = $statusMap[$request->status] ?? $request->status;

        return response()->json([
            'status' => 'success',
            'message' => "Kartu berhasil {$statusText}.",
            'data' => $card->fresh()->load('user')
        ]);
    }

    /**
     * Get card requests history
     */
    public function requestHistory(): JsonResponse
    {
        $requests = CardRequest::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $requests
        ]);
    }
}