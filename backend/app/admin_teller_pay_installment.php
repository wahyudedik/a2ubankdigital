<?php
// File: app/admin_teller_pay_installment.php
// Penjelasan: Staf (Teller/CS) memproses pembayaran angsuran secara tunai.
// Revisi: Memastikan respons JSON menyertakan ID transaksi untuk dicetak.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/push_notification_helper.php';

// Role yang bisa mengakses: Teller, CS, Kepala Unit ke atas
$allowed_roles = [1, 2, 3, 5, 6];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$installment_id = $input['installment_id'] ?? 0;
$cash_amount = (float)($input['cash_amount'] ?? 0);

if ($installment_id <= 0 || $cash_amount <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Input tidak valid.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Kunci dan ambil detail angsuran yang akan dibayar
    $stmt_inst = $pdo->prepare("
        SELECT li.*, l.user_id 
        FROM loan_installments li
        JOIN loans l ON li.loan_id = l.id
        WHERE li.id = ? AND li.status IN ('PENDING', 'OVERDUE') FOR UPDATE
    ");
    $stmt_inst->execute([$installment_id]);
    $installment = $stmt_inst->fetch(PDO::FETCH_ASSOC);

    if (!$installment) {
        throw new Exception("Angsuran tidak ditemukan, sudah lunas, atau sedang diproses.");
    }

    $total_due = (float)$installment['amount_due'] + (float)($installment['penalty_amount'] ?? 0);

    if ($cash_amount < $total_due) {
        throw new Exception("Jumlah uang tunai yang diterima kurang dari total tagihan (Rp " . number_format($total_due, 2, ',', '.') . ").");
    }

    // 2. Catat transaksi pembayaran tunai
    $description = "Bayar Angsuran Tunai Pinjaman #" . $installment['loan_id'] . " ke-" . $installment['installment_number'] . " via Teller #" . $authenticated_user_id;
    $stmt_trx = $pdo->prepare(
        "INSERT INTO transactions (to_account_id, transaction_type, amount, description, status, processed_by) 
        VALUES (?, 'BAYAR_CICILAN_TUNAI', ?, ?, 'SUCCESS', ?)"
    );
    $stmt_trx->execute([null, $total_due, $description, $authenticated_user_id]);
    $transaction_id = $pdo->lastInsertId();
    
    // 3. Update status angsuran menjadi 'PAID' dan tautkan transaction_id
    $stmt_update_inst = $pdo->prepare("UPDATE loan_installments SET status = 'PAID', payment_date = NOW(), transaction_id = ? WHERE id = ?");
    $stmt_update_inst->execute([$transaction_id, $installment_id]);

    // 4. Catat di Log Audit
    $audit_details = json_encode([
        'transaction_id' => $transaction_id,
        'customer_user_id' => $installment['user_id'],
        'installment_id' => $installment_id,
        'amount_paid' => $total_due
    ]);
    $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'TELLER_LOAN_PAYMENT', ?, ?)");
    $stmt_audit->execute([$authenticated_user_id, $audit_details, $_SERVER['REMOTE_ADDR']]);

    $pdo->commit();

    // 5. Kirim notifikasi ke nasabah
    try {
        $customer_user_id = $installment['user_id'];
        $title = "Pembayaran Angsuran Diterima";
        $message = "Kami telah menerima pembayaran tunai Anda sebesar " . number_format($total_due, 2, ',', '.') . " untuk angsuran pinjaman Anda. Terima kasih.";
        
        $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)")
            ->execute([$customer_user_id, $title, $message]);
        
        sendPushNotification($pdo, $customer_user_id, $title, $message);
    } catch (Exception $e) {
        error_log("Gagal mengirim notifikasi pembayaran angsuran tunai: " . $e->getMessage());
    }

    http_response_code(200);
    echo json_encode([
        'status' => 'success', 
        'message' => 'Pembayaran angsuran berhasil diterima.',
        'data' => ['transaction_id' => $transaction_id] // <-- PERUBAHAN DI SINI
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memproses pembayaran: ' . $e->getMessage()]);
}

