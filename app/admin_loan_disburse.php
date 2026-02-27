<?php
// File: app/admin_loan_disburse.php
// Penjelasan: Staf mencairkan dana dan mengirim notifikasi.
// REVISI: Logika pembuatan jadwal angsuran dirombak total untuk mendukung tenor HARI, MINGGU, dan BULAN.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/notification_helper.php';
require_once __DIR__ . '/helpers/push_notification_helper.php';

if ($authenticated_user_role_id > 3) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$loan_id = $input['loan_id'] ?? 0;

if ($loan_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Pinjaman tidak valid.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Ambil detail pinjaman, REVISI: Mengambil tenor dan tenor_unit
    $stmt_loan = $pdo->prepare(
        "SELECT 
            l.id, l.user_id, l.account_id, l.loan_amount, l.tenor, l.tenor_unit,
            lp.product_name, lp.interest_rate_pa,
            u.full_name as customer_name
         FROM loans l
         JOIN loan_products lp ON l.loan_product_id = lp.id
         JOIN users u ON l.user_id = u.id
         WHERE l.id = ? AND l.status = 'APPROVED' FOR UPDATE"
    );
    $stmt_loan->execute([$loan_id]);
    $loan = $stmt_loan->fetch(PDO::FETCH_ASSOC);

    if (!$loan) {
        throw new Exception("Pinjaman tidak ditemukan atau statusnya bukan 'APPROVED'.");
    }

    // 2. Transfer dana ke rekening tabungan nasabah (tidak berubah)
    $stmt_credit = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
    $stmt_credit->execute([$loan['loan_amount'], $loan['account_id']]);

    // 3. Catat transaksi pencairan (tidak berubah)
    $desc = "Pencairan Pinjaman " . $loan['product_name'];
    $stmt_trx = $pdo->prepare("INSERT INTO transactions (to_account_id, transaction_type, amount, description, status) VALUES (?, 'PENCAIRAN_PINJAMAN', ?, ?, 'SUCCESS')");
    $stmt_trx->execute([$loan['account_id'], $loan['loan_amount'], $desc]);
    $transaction_id = $pdo->lastInsertId();

    // 4. Update status pinjaman (tidak berubah)
    $stmt_update_loan = $pdo->prepare("UPDATE loans SET status = 'DISBURSED', disbursement_date = NOW() WHERE id = ?");
    $stmt_update_loan->execute([$loan_id]);

    // --- 5. REVISI TOTAL: LOGIKA PEMBUATAN JADWAL ANGSURAN ---
    $principal = (float)$loan['loan_amount'];
    $rate_pa = (float)$loan['interest_rate_pa'];
    $tenor = (int)$loan['tenor'];
    $tenor_unit = $loan['tenor_unit'];

    if ($tenor <= 0 || $rate_pa <= 0) {
        throw new Exception("Data tenor atau suku bunga tidak valid untuk menghitung angsuran.");
    }
    
    // Tentukan jumlah periode dalam setahun dan string interval untuk tanggal
    $periods_in_year = 0;
    $interval_string_base = '';
    switch ($tenor_unit) {
        case 'HARI':
            $periods_in_year = 365;
            $interval_string_base = 'day';
            break;
        case 'MINGGU':
            $periods_in_year = 52;
            $interval_string_base = 'week';
            break;
        case 'BULAN':
        default:
            $periods_in_year = 12;
            $interval_string_base = 'month';
            break;
    }
    
    // Hitung bunga per periode dan cicilan per periode menggunakan rumus anuitas
    $rate_per_period = ($rate_pa / 100) / $periods_in_year;
    $payment_per_period = ($principal * $rate_per_period) / (1 - pow(1 + $rate_per_period, -$tenor));
    $remaining_principal = $principal;
    
    $stmt_installment = $pdo->prepare(
        "INSERT INTO loan_installments (loan_id, installment_number, due_date, amount_due, principal_amount, interest_amount) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    for ($i = 1; $i <= $tenor; $i++) {
        $interest_component = $remaining_principal * $rate_per_period;
        $principal_component = $payment_per_period - $interest_component;
        
        // Buat tanggal jatuh tempo sesuai interval
        $interval_string = "+$i $interval_string_base";
        $due_date = date('Y-m-d', strtotime($interval_string));

        $stmt_installment->execute([
            $loan_id, $i, $due_date, 
            round($payment_per_period, 2), 
            round($principal_component, 2), 
            round($interest_component, 2)
        ]);
        
        $remaining_principal -= $principal_component;
    }
    // --- AKHIR REVISI TOTAL ---

    // 6. Log Audit (tidak berubah)
    $audit_details = json_encode(['loan_id' => $loan_id, 'transaction_id' => $transaction_id]);
    $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'DISBURSE_LOAN', ?, ?)");
    $stmt_audit->execute([$authenticated_user_id, $audit_details, $_SERVER['REMOTE_ADDR']]);

    $pdo->commit();

    // 7. Kirim Notifikasi (tidak berubah)
    try {
        $customer_user_id = $loan['user_id'];
        $title_customer = "Dana Pinjaman Telah Dicairkan";
        $message_customer = "Dana pinjaman Anda sebesar " . number_format($loan['loan_amount']) . " telah berhasil dicairkan ke rekening tabungan Anda.";
        
        $stmt_notify_customer = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $stmt_notify_customer->execute([$customer_user_id, $title_customer, $message_customer]);
        sendPushNotification($pdo, $customer_user_id, $title_customer, $message_customer);

        $title_staff = "Pencairan Pinjaman";
        $message_staff = "Dana untuk pinjaman nasabah " . $loan['customer_name'] . " (" . $loan['product_name'] . ") telah berhasil dicairkan.";
        notify_staff_by_role($pdo, [1, 2, 3], $title_staff, $message_staff);

    } catch (Exception $e) {
        error_log("Gagal mengirim notifikasi pencairan pinjaman: " . $e->getMessage());
    }

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Pinjaman berhasil dicairkan dan jadwal angsuran telah dibuat.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Loan Disbursement Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mencairkan pinjaman: ' . $e->getMessage()]);
}
