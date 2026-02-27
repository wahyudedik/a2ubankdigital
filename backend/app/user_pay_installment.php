<?php
// File: app/user_pay_installment.php
// Penjelasan: Nasabah membayar angsuran, lalu mengirim notifikasi ke staf.
// REVISI: Menambahkan perhitungan denda ke dalam total pembayaran.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/notification_helper.php';
require_once __DIR__ . '/helpers/push_notification_helper.php';

$input = json_decode(file_get_contents('php://input'), true);
$installment_id = $input['installment_id'] ?? 0;

if ($installment_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Angsuran tidak valid.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Kunci dan ambil data angsuran, sekarang bisa untuk status PENDING atau OVERDUE
    $stmt_inst = $pdo->prepare("
        SELECT li.*, l.user_id, u.full_name as customer_name
        FROM loan_installments li
        JOIN loans l ON li.loan_id = l.id
        JOIN users u ON l.user_id = u.id
        WHERE li.id = ? AND li.status IN ('PENDING', 'OVERDUE') FOR UPDATE
    ");
    $stmt_inst->execute([$installment_id]);
    $installment = $stmt_inst->fetch(PDO::FETCH_ASSOC);

    if (!$installment || $installment['user_id'] != $authenticated_user_id) {
        throw new Exception("Angsuran tidak ditemukan, sudah dibayar, atau bukan milik Anda.");
    }
    
    // 2. Kunci dan ambil rekening tabungan nasabah
    $stmt_acc = $pdo->prepare("SELECT id, balance FROM accounts WHERE user_id = ? AND account_type = 'TABUNGAN' FOR UPDATE");
    $stmt_acc->execute([$authenticated_user_id]);
    $savings_account = $stmt_acc->fetch(PDO::FETCH_ASSOC);

    // --- PERUBAHAN UTAMA: HITUNG TOTAL PEMBAYARAN ---
    $amount_due = (float)$installment['amount_due'];
    $penalty_amount = (float)$installment['penalty_amount'];
    $total_payment = $amount_due + $penalty_amount;
    // --- AKHIR PERUBAHAN ---

    if (!$savings_account || (float)$savings_account['balance'] < $total_payment) {
        throw new Exception("Rekening tabungan tidak ditemukan atau saldo tidak mencukupi untuk membayar angsuran beserta denda.");
    }

    // 3. Potong saldo (debit) rekening tabungan sejumlah total pembayaran
    $stmt_debit = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
    $stmt_debit->execute([$total_payment, $savings_account['id']]);

    // 4. Catat transaksi pembayaran dengan deskripsi yang disesuaikan
    $desc = "Bayar Angsuran Pinjaman #" . $installment['loan_id'] . " ke-" . $installment['installment_number'];
    if ($penalty_amount > 0) {
        $desc .= " (termasuk denda " . number_format($penalty_amount, 0, ',', '.') . ")";
    }
    $stmt_trx = $pdo->prepare("INSERT INTO transactions (from_account_id, transaction_type, amount, description, status) VALUES (?, 'BAYAR_CICILAN', ?, ?, 'SUCCESS')");
    $stmt_trx->execute([$savings_account['id'], $total_payment, $desc]);
    $transaction_id = $pdo->lastInsertId();

    // 5. Update status angsuran menjadi 'PAID'
    $stmt_update_inst = $pdo->prepare("UPDATE loan_installments SET status = 'PAID', payment_date = NOW(), transaction_id = ? WHERE id = ?");
    $stmt_update_inst->execute([$transaction_id, $installment_id]);

    $pdo->commit();
    
    // --- Kirim Notifikasi setelah proses berhasil ---
    try {
        // a. Notifikasi di dalam aplikasi untuk NASABAH dengan total pembayaran
        $title_customer = "Pembayaran Angsuran Berhasil";
        $message_customer = "Pembayaran angsuran Anda sebesar " . number_format($total_payment, 0, ',', '.') . " telah berhasil.";
        $stmt_notify_customer = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $stmt_notify_customer->execute([$authenticated_user_id, $title_customer, $message_customer]);

        // b. Notifikasi untuk STAF yang relevan
        $title_staff = "Pembayaran Angsuran Diterima";
        $message_staff = "Nasabah " . $installment['customer_name'] . " telah berhasil membayar angsuran ke-" . $installment['installment_number'] . " sebesar " . number_format($total_payment, 0, ',', '.');
        
        $target_roles = [1, 2, 3, 7]; // Analis Kredit & Manajer
        notify_staff_by_role($pdo, $target_roles, $title_staff, $message_staff);

    } catch (Exception $e) {
        error_log("Gagal mengirim notifikasi pembayaran angsuran: " . $e->getMessage());
    }
    // --------------------------------------------------

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Pembayaran angsuran berhasil.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500); 
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
