<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$loan = DB::table('loans')->orderBy('id', 'desc')->first();
if ($loan) {
    echo "Latest Loan:\n";
    print_r($loan);
    
    $installments = DB::table('loan_installments')->where('loan_id', $loan->id)->get();
    echo "\nInstallments for Loan {$loan->id}:\n";
    print_r($installments);
}

$loanProduct = DB::table('loan_products')->get();
echo "\nLoan Products:\n";
print_r($loanProduct);
