<?php

namespace App\Console\Commands;

use App\Models\LoanInstallment;
use App\Models\LoanProduct;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckOverdueInstallments extends Command
{
    protected $signature = 'loans:check-overdue';
    protected $description = 'Check and mark overdue loan installments, calculate late fees, notify customers';

    public function handle()
    {
        $overdue = LoanInstallment::where('status', 'PENDING')
            ->where('due_date', '<', now()->toDateString())
            ->with('loan.loanProduct')
            ->get();

        $count = 0;
        foreach ($overdue as $installment) {
            // Calculate late fee
            $daysOverdue = now()->diffInDays($installment->due_date);
            $latePaymentFee = $installment->loan->loanProduct->late_payment_fee ?? 0;
            
            // Calculate late fee: daily fee * days overdue
            $lateFee = $latePaymentFee * $daysOverdue;
            
            $installment->update([
                'status' => 'OVERDUE',
                'late_fee' => $lateFee
            ]);
            
            $count++;

            if ($installment->loan) {
                $totalDue = $installment->total_amount + $lateFee;
                
                app(NotificationService::class)->notifyUser(
                    $installment->loan->user_id,
                    'Angsuran Jatuh Tempo',
                    'Angsuran pinjaman Anda ke-' . $installment->installment_number . ' sebesar Rp ' . number_format($installment->total_amount, 0, ',', '.') . ' telah melewati jatuh tempo. Denda keterlambatan: Rp ' . number_format($lateFee, 0, ',', '.') . '. Total yang harus dibayar: Rp ' . number_format($totalDue, 0, ',', '.') . '. Segera lakukan pembayaran.'
                );
            }
        }

        $this->info("Marked {$count} installments as overdue and calculated late fees");
    }
}
