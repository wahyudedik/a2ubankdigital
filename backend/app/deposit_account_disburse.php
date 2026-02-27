<?php
// File: app/deposit_account_disburse.php
// Penjelasan: Nasabah mencairkan dana dari deposito yang sudah jatuh tempo.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/notification_helper.php';
require_once __DIR__ . '/helpers/push_notification_helper.php';

$input = json_decode(file_get_contents('php://input'), true);
$deposit_id = $input['deposit_id'] ?? 0;

if ($deposit_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Deposito tidak valid.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Kunci dan ambil detail deposito yang akan dicairkan
    $stmt_dep = $pdo->prepare("
        SELECT a.*, dp.interest_rate_pa, dp.tenor_months, dp.product_name 
        FROM accounts a
        JOIN deposit_products dp ON a.deposit_product_id = dp.id
        WHERE a.id = ? AND a.user_id = ? AND a.account_type = 'DEPOSITO' AND a.status = 'ACTIVE' FOR UPDATE
    ");
    $stmt_dep->execute([$deposit_id, $authenticated_user_id]);
    $deposit = $stmt_dep->fetch(PDO::FETCH_ASSOC);

    if (!$deposit) {
        throw new Exception("Rekening deposito tidak ditemukan atau statusnya tidak aktif.");
    }
    
    // 2. Cek apakah sudah jatuh tempo
    if (strtotime($deposit['maturity_date']) > time()) {
        throw new Exception("Deposito belum jatuh tempo dan tidak dapat dicairkan.");
    }
    
    // 3. Hitung bunga final (simple interest)
    $principal = (float)$deposit['balance'];
    $rate_pa = (float)$deposit['interest_rate_pa'];
    $placement_date = new DateTime($deposit['created_at']);
    $maturity_date = new DateTime($deposit['maturity_date']);
    $days_invested = $maturity_date->diff($placement_date)->days;
    $interest = ($principal * ($rate_pa / 100) * $days_invested) / 365;
    $total_disbursement = $principal + $interest;
    
    // 4. Kunci dan kredit rekening tabungan nasabah
    $stmt_sav = $pdo->prepare("SELECT id FROM accounts WHERE user_id = ? AND account_type = 'TABUNGAN' AND status = 'ACTIVE' FOR UPDATE");
    $stmt_sav->execute([$authenticated_user_id]);
    $savings_id = $stmt_sav->fetchColumn();
    if (!$savings_id) {
        throw new Exception("Rekening tabungan tidak ditemukan untuk menerima pencairan.");
    }
    
    $stmt_credit = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
    $stmt_credit->execute([$total_disbursement, $savings_id]);

    // 5. Ubah status rekening deposito menjadi MATURED (atau CLOSED)
    $stmt_close_dep = $pdo->prepare("UPDATE accounts SET status = 'MATURED' WHERE id = ?");
    $stmt_close_dep->execute([$deposit_id]);

    // 6. Catat transaksi pencairan
    $desc = "Pencairan " . $deposit['product_name'];
    $stmt_log = $pdo->prepare(
        "INSERT INTO transactions (from_account_id, to_account_id, transaction_type, amount, description, status) 
         VALUES (?, ?, 'PENCAIRAN_DEPOSITO', ?, ?, 'SUCCESS')"
    );
    $stmt_log->execute([$deposit_id, $savings_id, $total_disbursement, $desc]);

    $pdo->commit();

    // --- Kirim Notifikasi ---
    try {
        $title = "Deposito Telah Dicairkan";
        $message = "Deposito Anda (" . $deposit['product_name'] . ") telah berhasil dicairkan sebesar " . number_format($total_disbursement, 0, ',', '.') . " ke rekening tabungan Anda.";
        
        $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $stmt_notify->execute([$authenticated_user_id, $title, $message]);
        sendPushNotification($pdo, $authenticated_user_id, $title, $message);

    } catch (Exception $e) {
        error_log("Gagal mengirim notifikasi pencairan deposito: " . $e->getMessage());
    }
    // -----------------------
    
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Deposito berhasil dicairkan.']);

} catch (Exception $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mencairkan deposito: ' . $e->getMessage()]);
}
