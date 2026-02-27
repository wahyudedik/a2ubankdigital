<?php
// File: app/utility_loan_calculator.php
// Penjelasan: Simulasi perhitungan cicilan pinjaman.
// REVISI: Menambahkan logika untuk menghitung cicilan berdasarkan satuan tenor (HARI, MINGGU, BULAN).

require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$amount = (float)($input['amount'] ?? 0);
$tenor = (int)($input['tenor'] ?? 0);
$tenor_unit = $input['tenor_unit'] ?? 'BULAN';
$interest_rate_pa = (float)($input['interest_rate_pa'] ?? 0); // Bunga per tahun

if ($amount <= 0 || $tenor <= 0 || $interest_rate_pa <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Input tidak valid.']);
    exit();
}

// Logika perhitungan cicilan flat yang disesuaikan
$principal_per_period = $amount / $tenor;
$interest_per_period = 0;

switch ($tenor_unit) {
    case 'HARI':
        $interest_per_period = ($amount * ($interest_rate_pa / 100)) / 365;
        break;
    case 'MINGGU':
        $interest_per_period = ($amount * ($interest_rate_pa / 100)) / 52;
        break;
    case 'BULAN':
    default:
        $interest_per_period = ($amount * ($interest_rate_pa / 100)) / 12;
        break;
}

$total_installment_per_period = $principal_per_period + $interest_per_period;
$total_payment = $total_installment_per_period * $tenor;
$total_interest = $total_payment - $amount;

$result = [
    'loan_amount' => $amount,
    'tenor' => $tenor,
    'tenor_unit' => $tenor_unit,
    'interest_rate_pa' => $interest_rate_pa,
    'installment_per_period' => round($total_installment_per_period, 2),
    'total_payment' => round($total_payment, 2),
    'total_interest' => round($total_interest, 2)
];

http_response_code(200);
echo json_encode(['status' => 'success', 'data' => $result]);
?>
