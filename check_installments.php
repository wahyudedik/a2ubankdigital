<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$loan = DB::table('loans')->where('id', 161)->first();
if ($loan) {
    echo "Loan 161 Found:\n";
    print_r($loan);
    $installments = DB::table('loan_installments')->where('loan_id', 161)->get();
    print_r($installments);
} else {
    echo "Loan 161 not found. Searching for any loan with installments...\n";
    $loanIds = DB::table('loan_installments')->distinct()->pluck('loan_id')->toArray();
    echo "Loans with installments: " . implode(', ', $loanIds) . "\n";
    
    foreach ($loanIds as $id) {
        $l = DB::table('loans')->where('id', $id)->first();
        if ($l) {
            echo "\nLoan ID: {$l->id}, Status: {$l->status}, Amount: {$l->loan_amount}\n";
            $installments = DB::table('loan_installments')->where('loan_id', $l->id)->get();
            foreach ($installments as $inst) {
                echo "  Inst #{$inst->installment_number}: due_date={$inst->due_date}, total={$inst->total_amount}, paid={$inst->paid_amount}, late_fee={$inst->late_fee}, status={$inst->status}\n";
            }
        }
    }
}

// Check for any negative late fee records
$negFees = DB::table('loan_installments')->where('late_fee', '<', 0)->get();
echo "\nNegative late fee installments count: " . $negFees->count() . "\n";
foreach ($negFees as $nf) {
    echo "  ID: {$nf->id}, Loan ID: {$nf->loan_id}, Inst #: {$nf->installment_number}, late_fee: {$nf->late_fee}\n";
}
