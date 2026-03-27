<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ScheduledTransfer;
use App\Models\StandingInstruction;
use App\Models\Account;
use App\Services\LogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScheduledTransferController extends Controller
{
    protected $logService;
    protected $notificationService;

    public function __construct(LogService $logService, NotificationService $notificationService)
    {
        $this->logService = $logService;
        $this->notificationService = $notificationService;
    }

    /**
     * Create scheduled transfer
     */
    public function scheduleTransfer(Request $request): JsonResponse
    {
        $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_number' => 'required|string|max:20',
            'amount' => 'required|numeric|min:1',
            'description' => 'sometimes|string|max:255',
            'scheduled_date' => 'required|date|after:today'
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

        // Check if sufficient balance (including potential fees)
        $transferFee = 2500; // Standard internal transfer fee
        $totalAmount = $request->amount + $transferFee;

        if ($fromAccount->balance < $totalAmount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Saldo tidak mencukupi untuk transfer terjadwal ini.'
            ], 400);
        }

        try {
            $scheduledTransfer = ScheduledTransfer::create([
                'user_id' => $user->id,
                'from_account_id' => $request->from_account_id,
                'to_account_number' => $request->to_account_number,
                'amount' => $request->amount,
                'description' => $request->description ?? 'Transfer Terjadwal',
                'scheduled_date' => $request->scheduled_date,
                'status' => 'PENDING'
            ]);

            // Log the scheduling
            $this->logService->logAudit('SCHEDULED_TRANSFER_CREATED', 'scheduled_transfers', $scheduledTransfer->id, [], [
                'amount' => $request->amount,
                'scheduled_date' => $request->scheduled_date,
                'to_account_number' => $request->to_account_number
            ]);

            // Notify user
            $this->notificationService->notifyUser(
                $user->id,
                'Transfer Terjadwal Dibuat',
                'Transfer sebesar Rp ' . number_format($request->amount, 2, ',', '.') . ' telah dijadwalkan untuk tanggal ' . $request->scheduled_date . '.'
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Transfer berhasil dijadwalkan.',
                'data' => $scheduledTransfer->fresh(['fromAccount'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat transfer terjadwal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's scheduled transfers
     */
    public function getScheduledTransfers(Request $request): JsonResponse
    {
        $user = Auth::user();
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $status = $request->input('status');

        $query = ScheduledTransfer::with(['fromAccount'])
            ->where('user_id', $user->id);

        if ($status) {
            $query->where('status', $status);
        }

        $totalRecords = $query->count();
        $transfers = $query
            ->orderBy('scheduled_date', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $transfers,
            'pagination' => [
                'current_page' => (int)$page,
                'total_records' => (int)$totalRecords
            ]
        ]);
    }

    /**
     * Cancel scheduled transfer
     */
    public function cancelScheduledTransfer($id): JsonResponse
    {
        $user = Auth::user();

        $scheduledTransfer = ScheduledTransfer::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', 'PENDING')
            ->first();

        if (!$scheduledTransfer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transfer terjadwal tidak ditemukan atau sudah dieksekusi.'
            ], 404);
        }

        // Check if scheduled date is not today (can't cancel same-day transfers)
        if ($scheduledTransfer->scheduled_date <= now()->toDateString()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat membatalkan transfer yang dijadwalkan hari ini atau sudah lewat.'
            ], 400);
        }

        $scheduledTransfer->update(['status' => 'FAILED', 'failure_reason' => 'Dibatalkan oleh pengguna']);

        // Log the cancellation
        $this->logService->logAudit('SCHEDULED_TRANSFER_CANCELLED', 'scheduled_transfers', $scheduledTransfer->id);

        // Notify user
        $this->notificationService->notifyUser(
            $user->id,
            'Transfer Terjadwal Dibatalkan',
            'Transfer terjadwal sebesar Rp ' . number_format($scheduledTransfer->amount, 2, ',', '.') . ' telah berhasil dibatalkan.'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Transfer terjadwal berhasil dibatalkan.'
        ]);
    }

    /**
     * Create standing instruction
     */
    public function createStandingInstruction(Request $request): JsonResponse
    {
        $request->validate([
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_number' => 'required|string|max:20',
            'to_bank_code' => 'sometimes|string|max:10',
            'amount' => 'required|numeric|min:1',
            'description' => 'sometimes|string|max:255',
            'frequency' => 'required|in:MONTHLY,WEEKLY',
            'execution_day' => 'required|integer|min:1|max:31',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'sometimes|date|after:start_date'
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

        // Validate execution day based on frequency
        if ($request->frequency === 'WEEKLY' && ($request->execution_day < 0 || $request->execution_day > 6)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hari eksekusi untuk mingguan harus antara 0-6 (0=Minggu, 6=Sabtu).'
            ], 400);
        }

        if ($request->frequency === 'MONTHLY' && ($request->execution_day < 1 || $request->execution_day > 31)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hari eksekusi untuk bulanan harus antara 1-31.'
            ], 400);
        }

        try {
            $standingInstruction = StandingInstruction::create([
                'user_id' => $user->id,
                'from_account_id' => $request->from_account_id,
                'to_account_number' => $request->to_account_number,
                'to_bank_code' => $request->to_bank_code,
                'amount' => $request->amount,
                'description' => $request->description ?? 'Standing Instruction',
                'frequency' => $request->frequency,
                'execution_day' => $request->execution_day,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => 'ACTIVE'
            ]);

            // Log the creation
            $this->logService->logAudit('STANDING_INSTRUCTION_CREATED', 'standing_instructions', $standingInstruction->id, [], [
                'amount' => $request->amount,
                'frequency' => $request->frequency,
                'execution_day' => $request->execution_day
            ]);

            // Notify user
            $this->notificationService->notifyUser(
                $user->id,
                'Standing Instruction Dibuat',
                'Standing instruction ' . $request->frequency . ' sebesar Rp ' . number_format($request->amount, 2, ',', '.') . ' telah berhasil dibuat.'
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Standing instruction berhasil dibuat.',
                'data' => $standingInstruction->fresh(['fromAccount'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat standing instruction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's standing instructions
     */
    public function getStandingInstructions(Request $request): JsonResponse
    {
        $user = Auth::user();
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $status = $request->input('status');

        $query = StandingInstruction::with(['fromAccount'])
            ->where('user_id', $user->id);

        if ($status) {
            $query->where('status', $status);
        }

        $totalRecords = $query->count();
        $instructions = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $instructions,
            'pagination' => [
                'current_page' => (int)$page,
                'total_records' => (int)$totalRecords
            ]
        ]);
    }

    /**
     * Update standing instruction status
     */
    public function updateStandingInstructionStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:ACTIVE,PAUSED,ENDED'
        ]);

        $user = Auth::user();

        $instruction = StandingInstruction::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$instruction) {
            return response()->json([
                'status' => 'error',
                'message' => 'Standing instruction tidak ditemukan.'
            ], 404);
        }

        $oldStatus = $instruction->status;
        $instruction->update(['status' => $request->status]);

        // Log the status change
        $this->logService->logAudit('STANDING_INSTRUCTION_STATUS_CHANGED', 'standing_instructions', $instruction->id, 
            ['status' => $oldStatus], 
            ['status' => $request->status]
        );

        // Notify user
        $statusText = [
            'ACTIVE' => 'diaktifkan',
            'PAUSED' => 'dijeda',
            'ENDED' => 'dihentikan'
        ];

        $this->notificationService->notifyUser(
            $user->id,
            'Status Standing Instruction Diperbarui',
            'Standing instruction Anda telah ' . $statusText[$request->status] . '.'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Status standing instruction berhasil diperbarui.'
        ]);
    }
}