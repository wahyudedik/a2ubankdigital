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
            'daily_limit' => 'required|numeric|min:0|max:50000000'
        ]);

        $card = Card::where('user_id', Auth::id())
            ->where('status', 'ACTIVE')
            ->findOrFail($id);

        $card->update([
            'daily_limit' => $request->daily_limit
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Limit kartu berhasil diperbarui.',
            'data' => $card
        ]);
    }

    /**
     * Update card status (block/unblock)
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:ACTIVE,BLOCKED'
        ]);

        $card = Card::where('user_id', Auth::id())
            ->findOrFail($id);

        $card->update([
            'status' => $request->status
        ]);

        $statusText = $request->status === 'ACTIVE' ? 'diaktifkan' : 'diblokir';

        return response()->json([
            'status' => 'success',
            'message' => "Kartu berhasil {$statusText}.",
            'data' => $card
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