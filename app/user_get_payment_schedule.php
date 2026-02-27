<?php
// File: app/user_get_payment_schedule.php
// Penjelasan: Nasabah melihat jadwal cicilan untuk pinjaman aktif.
// REVISI: Mengganti kolom 'loan_account_id' dengan 'loan_id' yang benar.

require_once 'auth_middleware.php';

$loan_id = $_GET['loan_id'] ?? null;
if (!$loan_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Pinjaman wajib diisi.']);
    exit();
}

try {
    // 1. Validasi kepemilikan pinjaman
    $stmt_acc = $pdo->prepare("SELECT id FROM loans WHERE id = ? AND user_id = ?");
    $stmt_acc->execute([$loan_id, $authenticated_user_id]);
    if (!$stmt_acc->fetch()) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Rekening pinjaman tidak ditemukan.']);
        exit();
    }
    
    // 2. Ambil jadwal cicilan dari pinjaman tersebut
    // REVISI: Mengubah 'loan_account_id' menjadi 'loan_id'
    $stmt_installments = $pdo->prepare("
        SELECT due_date, amount_due, penalty_amount, status, payment_date
        FROM loan_installments
        WHERE loan_id = ?
        ORDER BY due_date ASC
    ");
    $stmt_installments->execute([$loan_id]);
    $schedule = $stmt_installments->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $schedule]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil jadwal pembayaran.']);
}
