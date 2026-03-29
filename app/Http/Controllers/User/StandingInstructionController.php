<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\StandingInstruction;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StandingInstructionController extends Controller
{
    /**
     * Get all standing instructions for authenticated user
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        
        $instructions = StandingInstruction::where('user_id', $user->id)
            ->with(['fromAccount', 'toAccount'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($instruction) {
                return [
                    'id' => $instruction->id,
                    'from_account_number' => $instruction->fromAccount?->account_number,
                    'to_account_number' => $instruction->toAccount?->account_number,
                    'recipient_name' => $instruction->recipient_name,
                    'amount' => (float)$instruction->amount,
                    'instruction_type' => $instruction->instruction_type,
                    'execution_day' => $instruction->execution_day,
                    'start_date' => $instruction->start_date,
                    'end_date' => $instruction->end_date,
                    'description' => $instruction->description,
                    'status' => $instruction->status,
                    'created_at' => $instruction->created_at,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $instructions
        ]);
    }

    /**
     * Create new standing instruction
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'to_account_number' => 'required|string',
            'amount' => 'required|numeric|min:10000',
            'instruction_type' => 'required|in:MONTHLY,SPECIFIC_DATE',
            'execution_day' => 'required|integer|min:1|max:31',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'description' => 'nullable|string|max:255'
        ]);

        DB::beginTransaction();
        try {
            // Get user's savings account
            $fromAccount = Account::where('user_id', $user->id)
                ->where('account_type', 'TABUNGAN')
                ->where('status', 'ACTIVE')
                ->first();

            if (!$fromAccount) {
                throw new \Exception('Rekening sumber tidak ditemukan.');
            }

            // Get destination account
            $toAccount = Account::where('account_number', $request->to_account_number)
                ->where('status', 'ACTIVE')
                ->first();

            if (!$toAccount) {
                throw new \Exception('Rekening tujuan tidak ditemukan atau tidak aktif.');
            }

            // Check not transferring to self
            if ($toAccount->user_id == $user->id) {
                throw new \Exception('Tidak dapat membuat standing instruction ke rekening sendiri.');
            }

            // Create standing instruction
            $standingInstruction = StandingInstruction::create([
                'user_id' => $user->id,
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'recipient_name' => $toAccount->user->full_name,
                'amount' => $request->amount,
                'instruction_type' => $request->instruction_type,
                'execution_day' => $request->execution_day,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'description' => $request->description ?? 'Standing Instruction',
                'status' => 'ACTIVE'
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Standing instruction berhasil dibuat.',
                'data' => $standingInstruction
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat standing instruction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update standing instruction
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'amount' => 'sometimes|numeric|min:10000',
            'execution_day' => 'sometimes|integer|min:1|max:31',
            'end_date' => 'nullable|date',
            'description' => 'nullable|string|max:255',
            'status' => 'sometimes|in:ACTIVE,PAUSED'
        ]);

        $standingInstruction = StandingInstruction::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $standingInstruction->update($request->only([
            'amount', 'execution_day', 'end_date', 'description', 'status'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Standing instruction berhasil diperbarui.',
            'data' => $standingInstruction
        ]);
    }

    /**
     * Delete standing instruction
     */
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();

        $standingInstruction = StandingInstruction::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $standingInstruction->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Standing instruction berhasil dihapus.'
        ]);
    }
}
