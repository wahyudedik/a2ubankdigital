<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\StandingInstruction;
use App\Models\Transaction;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessStandingInstructions extends Command
{
    protected $signature = 'transfers:process-standing';
    protected $description = 'Process standing instructions due for execution today';

    public function handle()
    {
        $instructions = StandingInstruction::where('status', 'active')
            ->where('start_date', '<=', now()->toDateString())
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString()))
            ->get()
            ->filter(fn($i) => $i->shouldExecuteToday());

        $processed = 0;

        foreach ($instructions as $instruction) {
            DB::beginTransaction();
            try {
                $fromAccount = Account::where('id', $instruction->from_account_id)->lockForUpdate()->first();
                $toAccount = Account::where('account_number', $instruction->to_account_number)->first();

                if (!$fromAccount || !$toAccount || $fromAccount->balance < $instruction->amount) {
                    Log::warning("Standing instruction #{$instruction->id} skipped: insufficient balance or account not found");
                    DB::rollBack();
                    continue;
                }

                $fromAccount->decrement('balance', $instruction->amount);
                $toAccount->increment('balance', $instruction->amount);

                Transaction::create([
                    'transaction_code' => 'TRX-' . time() . '-' . rand(100000, 999999),
                    'from_account_id' => $fromAccount->id,
                    'to_account_id' => $toAccount->id,
                    'transaction_type' => 'TRANSFER_INTERNAL',
                    'amount' => $instruction->amount,
                    'fee' => 0,
                    'description' => $instruction->description ?? 'Standing Instruction',
                    'status' => 'SUCCESS',
                ]);

                $instruction->update(['last_executed' => now()->toDateString()]);
                $processed++;

                app(NotificationService::class)->notifyUser($instruction->user_id, 'Standing Instruction Dieksekusi', 'Transfer otomatis sebesar Rp ' . number_format($instruction->amount, 0, ',', '.') . ' telah berhasil dieksekusi.');

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Standing instruction #{$instruction->id} failed: " . $e->getMessage());
            }
        }

        $this->info("Processed: {$processed} standing instructions");
    }
}
