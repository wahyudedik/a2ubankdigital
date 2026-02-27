<?php
// File: app/user_get_estatement.php
// Penjelasan: Nasabah mengunduh rekapitulasi transaksi bulanan (e-statement).

require_once 'auth_middleware.php';

$account_id = $_GET['account_id'] ?? null;
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

if (!$account_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Rekening wajib diisi.']);
    exit();
}

try {
    // 1. Dapatkan info rekening & saldo awal
    $stmt_acc = $pdo->prepare("SELECT account_number, balance FROM accounts WHERE id = ? AND user_id = ?");
    $stmt_acc->execute([$account_id, $authenticated_user_id]);
    $account_info = $stmt_acc->fetch(PDO::FETCH_ASSOC);
    if (!$account_info) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Rekening tidak ditemukan.']);
        exit();
    }
    
    // 2. Ambil transaksi pada bulan yang dipilih
    $stmt_trx = $pdo->prepare("
        SELECT created_at, transaction_type, amount, description, 
               (CASE WHEN to_account_id = ? THEN 'CREDIT' ELSE 'DEBIT' END) as flow
        FROM transactions
        WHERE (from_account_id = ? OR to_account_id = ?) 
          AND MONTH(created_at) = ? AND YEAR(created_at) = ?
        ORDER BY created_at ASC
    ");
    $stmt_trx->execute([$account_id, $account_id, $account_id, $month, $year]);
    $transactions = $stmt_trx->fetchAll(PDO::FETCH_ASSOC);

    $response = [
        'period' => "$year-$month",
        'account_number' => $account_info['account_number'],
        'ending_balance' => (float)$account_info['balance'], // Saldo akhir adalah saldo saat ini
        'transactions' => $transactions
    ];
    
    // Di aplikasi nyata, Anda bisa menggunakan library seperti FPDF untuk men-generate PDF di sini.
    
    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $response]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil e-statement.']);
}
?>
