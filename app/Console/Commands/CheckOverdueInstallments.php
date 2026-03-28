<?php

namespace App\Console\Commands;

use App\Models\LoanInstallment;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckOverdueInstallments extends Command
{
    protected $signature = 'loans:check-overdue';
    protected $description = 'Check and mark overdue loan installments, notify customers';

    public function handle()
    {
        $overdue = LoanInstallment::where('status', 'PENDING')
            ->where('due_date', '<', now()->toDateString())
            ->with('loan')
            ->get();

        $count = 0;
        foreach ($overdue as $installment) {
            $installment->update(['status' => 'OVERDUE']);
            $count++;

            if ($installment->loan) {
                app(NotificationService::class)->notifyUser(
                    $installment->loan->user_id,
                    'Angsuran Jatuh Tempo',
                    'Angsuran pinjaman Anda ke-' . $installment->installment_number . ' sebesar Rp ' . number_format($installment->total_amount, 0, ',', '.') . ' telah melewati jatuh tempo. Segera lakukan pembayaran.'
                );
            }
        }

        $this->info("Marked {$count} installments as overdue");
    }
}
