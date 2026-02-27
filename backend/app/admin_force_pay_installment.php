<?php
// File: app/admin_force_pay_installment.php
// Penjelasan: Admin (Kepala Unit ke atas) memaksa pembayaran angsuran
// yang menunggak dengan memotong langsung dari saldo tabungan nasabah.
// REVISI: Memperbaiki logika query untuk mencakup status 'OVERDUE'.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/notification_helper.php';
require_once __DIR__ . '/helpers/push_notification_helper.php';

// Hanya Kepala Unit ke atas yang bisa melakukan aksi ini
if ($authenticated_user_role_id > 3) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Peran Anda tidak diizinkan.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$installment_id = $input['installment_id'] ?? 0;

if ($installment_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Angsuran tidak valid.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Kunci dan ambil data angsuran yang menunggak
    // --- PERBAIKAN UTAMA DI SINI ---
    // Sekarang mencari angsuran dengan status PENDING atau OVERDUE
    $stmt_inst = $pdo->prepare("
        SELECT li.*, l.user_id, u.full_name as customer_name
        FROM loan_installments li
        JOIN loans l ON li.loan_id = l.id
        JOIN users u ON l.user_id = u.id
        WHERE li.id = ? AND li.status IN ('PENDING', 'OVERDUE') AND li.due_date < CURDATE() FOR UPDATE
    ");
    $stmt_inst->execute([$installment_id]);
    $installment = $stmt_inst->fetch(PDO::FETCH_ASSOC);

    if (!$installment) {
        throw new Exception("Angsuran tidak ditemukan, sudah dibayar, atau belum jatuh tempo.");
    }

    $customer_user_id = $installment['user_id'];
    $amount_due = (float)$installment['amount_due'] + (float)$installment['penalty_amount'];

    // 2. Kunci dan ambil rekening tabungan nasabah
    $stmt_acc = $pdo->prepare("SELECT id, balance FROM accounts WHERE user_id = ? AND account_type = 'TABUNGAN' FOR UPDATE");
    $stmt_acc->execute([$customer_user_id]);
    $savings_account = $stmt_acc->fetch(PDO::FETCH_ASSOC);

    if (!$savings_account) {
        throw new Exception("Rekening tabungan nasabah tidak ditemukan.");
    }

    if ($savings_account['balance'] < $amount_due) {
        throw new Exception("Saldo nasabah tidak mencukupi untuk melunasi angsuran.");
    }

    // 3. Potong saldo (debit) rekening tabungan
    $stmt_debit = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
    $stmt_debit->execute([$amount_due, $savings_account['id']]);

    // 4. Catat transaksi pembayaran (dengan deskripsi khusus)
    $desc = "Pemotongan Saldo Angsuran #" . $installment['loan_id'] . " ke-" . $installment['installment_number'] . " oleh Admin";
    $stmt_trx = $pdo->prepare("INSERT INTO transactions (from_account_id, transaction_type, amount, description, status) VALUES (?, 'BAYAR_CICILAN_PAKSA', ?, ?, 'SUCCESS')");
    $stmt_trx->execute([$savings_account['id'], $amount_due, $desc]);
    $transaction_id = $pdo->lastInsertId();

    // 5. Update status angsuran menjadi 'PAID'
    $stmt_update_inst = $pdo->prepare("UPDATE loan_installments SET status = 'PAID', payment_date = NOW(), transaction_id = ? WHERE id = ?");
    $stmt_update_inst->execute([$transaction_id, $installment_id]);
    
    // 6. Catat di Log Audit
    $audit_details = json_encode(['installment_id' => $installment_id, 'loan_id' => $installment['loan_id'], 'customer_id' => $customer_user_id]);
    $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'FORCE_INSTALLMENT_PAYMENT', ?, ?)");
    $stmt_audit->execute([$authenticated_user_id, $audit_details, $_SERVER['REMOTE_ADDR']]);

    $pdo->commit();

    // 7. Kirim Notifikasi ke Nasabah
    try {
        $title = "Pembayaran Angsuran Otomatis";
        $message = "Sistem telah melakukan pemotongan saldo sebesar " . number_format($amount_due, 0, ',', '.') . " untuk pembayaran angsuran Anda yang tertunggak.";
        
        $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $stmt_notify->execute([$customer_user_id, $title, $message]);
        sendPushNotification($pdo, $customer_user_id, $title, $message);

    } catch (Exception $e) {
        error_log("Gagal mengirim notifikasi pemotongan saldo paksa: " . $e->getMessage());
    }

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Pemotongan saldo untuk pembayaran angsuran berhasil.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memproses: ' . $e->getMessage()]);
}
