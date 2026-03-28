<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\ScheduledTransfer;
use App\Models\Transaction;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessScheduledTransfers extends Command
{
    protected $signature = 'transfers:process-scheduled';
    protected $description = 'Process scheduled transfers that are due today';

    public function handle()
    {
        $dueTransfers = ScheduledTransfer::where('status', 'pending')
            ->where('scheduled_date', '<=', now()->toDateString())
            ->with('fromAccount')
            ->get();

        $processed = 0;
        $failed = 0;

        foreach ($dueTransfers as $transfer) {
            DB::beginTransaction();
            try {
                $fromAccount = Account::where('id', $transfer->from_account_id)->lockForUpdate()->first();
                $toAccount = Account::where('account_number', $transfer->to_account_number)->first();

                if (!$fromAccount || !$toAccount || $fromAccount->balance < $transfer->amount) {
                    $transfer->update(['status' => 'failed', 'failure_reason' => 'Saldo tidak mencukupi atau rekening tidak ditemukan']);
                    $failed++;
                    DB::commit();
                    continue;
                }

                $fromAccount->decrement('balance', $transfer->amount);
                $toAccount->increment('balance', $transfer->amount);

                Transaction::create([
                    'transaction_code' => 'TRX-' . time() . '-' . rand(100000, 999999),
                    'from_account_id' => $fromAccount->id,
                    'to_account_id' => $toAccount->id,
                    'transaction_type' => 'TRANSFER_INTERNAL',
                    'amount' => $transfer->amount,
                    'fee' => 0,
                    'description' => $transfer->description ?? 'Transfer Terjadwal',
                    'status' => 'SUCCESS',
                ]);

                $transfer->update(['status' => 'executed', 'executed_at' => now()]);
                $processed++;

                app(NotificationService::class)->notifyUser($transfer->user_id, 'Transfer Terjadwal Berhasil', 'Transfer sebesar Rp ' . number_format($transfer->amount, 0, ',', '.') . ' telah berhasil dieksekusi.');

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $transfer->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
                $failed++;
                Log::error("Scheduled transfer #{$transfer->id} failed: " . $e->getMessage());
            }
        }

        $this->info("Processed: {$processed}, Failed: {$failed}");
    }
}
