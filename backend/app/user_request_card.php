<?php
// File: app/user_request_card.php
// Penjelasan: Nasabah mengajukan permintaan kartu fisik.
// REVISI: Menggunakan sistem notifikasi berjenjang.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/notification_helper.php';

$input = json_decode(file_get_contents('php://input'), true);
$account_id = $input['account_id'] ?? null;
if (!$account_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Rekening Tabungan wajib diisi.']);
    exit();
}

try {
    $stmt_acc = $pdo->prepare("SELECT id FROM accounts WHERE id = ? AND user_id = ? AND account_type = 'TABUNGAN'");
    $stmt_acc->execute([$account_id, $authenticated_user_id]);
    if (!$stmt_acc->fetch()) throw new Exception("Rekening tabungan tidak valid.");

    $stmt_check = $pdo->prepare("SELECT id FROM cards WHERE account_id = ? AND status IN ('REQUESTED', 'ACTIVE')");
    $stmt_check->execute([$account_id]);
    if ($stmt_check->fetch()) throw new Exception("Anda sudah memiliki kartu atau sedang dalam proses pengajuan untuk rekening ini.");
    
    $dummy_masked_number = '5123-XXXX-XXXX-' . rand(1000, 9999);

    $stmt_insert = $pdo->prepare("INSERT INTO cards (user_id, account_id, card_number_masked) VALUES (?, ?, ?)");
    $stmt_insert->execute([$authenticated_user_id, $account_id, $dummy_masked_number]);
    
    // --- LOGIKA NOTIFIKASI BARU ---
    try {
        $stmt_user = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt_user->execute([$authenticated_user_id]);
        $customer_name = $stmt_user->fetchColumn();

        $title = "Permintaan Kartu Baru";
        $message = "Nasabah " . $customer_name . " telah mengajukan permintaan kartu fisik baru.";
        
        // Target peran pelaksana adalah Customer Service (role_id = 6).
        $target_role_id = 6;
        notify_staff_hierarchically($pdo, $authenticated_user_id, $target_role_id, $title, $message);

    } catch (Exception $e) {
        error_log("Gagal kirim notifikasi request kartu: " . $e->getMessage());
    }
    // ---------------------------------
    
    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Permintaan kartu Anda telah diterima dan akan segera diproses.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengajukan permintaan: ' . $e->getMessage()]);
}
