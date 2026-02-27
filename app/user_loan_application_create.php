<?php
// File: app/user_loan_application_create.php
// Penjelasan: Nasabah mengajukan pinjaman baru.
// REVISI: Menggunakan nama kolom tenor yang baru dan menyimpan tenor_unit.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/notification_helper.php';

$input = json_decode(file_get_contents('php://input'), true);

$required = ['loan_product_id', 'amount', 'tenor', 'purpose'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}

try {
    // 1. Validasi produk dengan kolom baru (min_tenor, max_tenor)
    $stmt_prod = $pdo->prepare("SELECT * FROM loan_products WHERE id = ? AND is_active = 1");
    $stmt_prod->execute([$input['loan_product_id']]);
    $product = $stmt_prod->fetch(PDO::FETCH_ASSOC);
    if (!$product) throw new Exception("Produk pinjaman tidak valid.");

    if ($input['amount'] < $product['min_amount'] || $input['amount'] > $product['max_amount']) {
        throw new Exception("Jumlah pinjaman tidak sesuai dengan ketentuan produk.");
    }
    // REVISI: Menggunakan kolom `min_tenor` dan `max_tenor`
    if ($input['tenor'] < $product['min_tenor'] || $input['tenor'] > $product['max_tenor']) {
        throw new Exception("Jangka waktu (tenor) tidak sesuai ketentuan produk.");
    }

    // Cek pengajuan lain yang sedang berjalan (tidak berubah)
    $stmt_check = $pdo->prepare("SELECT id FROM loans WHERE user_id = ? AND status IN ('SUBMITTED', 'ANALYZING', 'APPROVED')");
    $stmt_check->execute([$authenticated_user_id]);
    if ($stmt_check->fetch()) {
        throw new Exception("Anda masih memiliki pengajuan pinjaman yang sedang diproses.");
    }

    $pdo->beginTransaction();

    // Dapatkan rekening tabungan (tidak berubah)
    $stmt_acc = $pdo->prepare("SELECT id FROM accounts WHERE user_id = ? AND account_type = 'TABUNGAN' AND status = 'ACTIVE'");
    $stmt_acc->execute([$authenticated_user_id]);
    $account_id = $stmt_acc->fetchColumn();
    if (!$account_id) {
        throw new Exception("Rekening tabungan aktif tidak ditemukan.");
    }

    // 2. REVISI: Query INSERT sekarang menggunakan kolom `tenor` dan `tenor_unit`
    $sql = "INSERT INTO loans (user_id, account_id, loan_product_id, loan_amount, tenor, tenor_unit, interest_rate_pa, status, application_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'SUBMITTED', NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $authenticated_user_id,
        $account_id,
        $input['loan_product_id'],
        $input['amount'],
        $input['tenor'],
        $product['tenor_unit'], // <-- Menyimpan satuan tenor
        $product['interest_rate_pa']
    ]);
    $loan_id = $pdo->lastInsertId();

    $pdo->commit();

    // Logika notifikasi (tidak berubah)
    try {
        $stmt_user = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt_user->execute([$authenticated_user_id]);
        $customer_name = $stmt_user->fetchColumn();

        $title = "Pengajuan Pinjaman Baru";
        $message = "Nasabah " . $customer_name . " telah mengajukan pinjaman baru (" . $product['product_name'] . ").";
        
        $target_role_id = 7; 
        notify_staff_hierarchically($pdo, $authenticated_user_id, $target_role_id, $title, $message);
        
    } catch (Exception $e) {
        error_log("Gagal kirim notifikasi pinjaman baru: " . $e->getMessage());
    }
    
    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Pengajuan pinjaman Anda telah berhasil dikirim.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
