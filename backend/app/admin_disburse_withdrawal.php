<?php
// File: app/admin_disburse_withdrawal.php
// Penjelasan: Staf (Teller ke atas) mengeksekusi pencairan dana yang sudah disetujui.
// REVISI: Menambahkan validasi data scoping.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/notification_helper.php';
require_once __DIR__ . '/helpers/push_notification_helper.php';

// Hanya Teller ke atas yang bisa melakukan pencairan
if ($authenticated_user_role_id > 5) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$request_id = $input['request_id'] ?? 0;

if ($request_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Permintaan tidak valid.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Ambil detail permintaan yang statusnya 'APPROVED' dan kunci barisnya
    $stmt_req = $pdo->prepare("
        SELECT wr.*, cp.unit_id
        FROM withdrawal_requests wr
        JOIN customer_profiles cp ON wr.user_id = cp.user_id
        WHERE wr.id = ? AND wr.status = 'APPROVED' FOR UPDATE
    ");
    $stmt_req->execute([$request_id]);
    $request = $stmt_req->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception("Permintaan tidak ditemukan atau statusnya bukan 'APPROVED'.");
    }
    
    // --- PENAMBAHAN: VALIDASI DATA SCOPING ---
    if ($authenticated_user_role_id !== 1 && !in_array($request['unit_id'], $accessible_unit_ids)) {
        http_response_code(403);
        throw new Exception("Akses ditolak: Anda tidak berwenang mencairkan dana untuk nasabah di unit ini.");
    }
    // --- AKHIR PENAMBAHAN ---

    // 2. Temukan transaksi PENDING yang terkait dengan permintaan ini
    $stmt_trx = $pdo->prepare(
        "SELECT t.id FROM transactions t
         JOIN accounts a ON t.from_account_id = a.id
         WHERE a.user_id = ? AND t.transaction_type = 'TARIK_TUNAI' AND t.status = 'PENDING' AND t.amount = ?
         ORDER BY t.created_at DESC LIMIT 1"
    );
    $stmt_trx->execute([$request['user_id'], $request['amount']]);
    $transaction_id = $stmt_trx->fetchColumn();

    if (!$transaction_id) {
        throw new Exception("Transaksi terkait untuk diselesaikan tidak ditemukan. Harap hubungi IT support.");
    }
    
    // 3. Update status transaksi menjadi SUCCESS
    $stmt_update_trx = $pdo->prepare("UPDATE transactions SET status = 'SUCCESS' WHERE id = ?");
    $stmt_update_trx->execute([$transaction_id]);

    // 4. Update status permintaan penarikan menjadi COMPLETED
    $stmt_update_req = $pdo->prepare("UPDATE withdrawal_requests SET status = 'COMPLETED' WHERE id = ?");
    $stmt_update_req->execute([$request_id]);

    // 5. CATAT DI LOG AUDIT
    $details = json_encode(['withdrawal_request_id' => $request_id, 'transaction_id' => $transaction_id]);
    $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'DISBURSE_WITHDRAWAL', ?, ?)");
    $stmt_audit->execute([$authenticated_user_id, $details, $_SERVER['REMOTE_ADDR']]);

    // Di aplikasi produksi, di sinilah Anda akan memanggil API pihak ketiga untuk transfer antar bank.

    $pdo->commit();

    // Kirim notifikasi ke nasabah bahwa dana telah berhasil ditransfer
    try {
        $customer_user_id = $request['user_id'];
        $amount_formatted = number_format((float)$request['amount'], 0, ',', '.');
        $title = "Pencairan Dana Berhasil";
        $message = "Dana sebesar Rp {$amount_formatted} telah berhasil kami transfer ke rekening tujuan Anda.";
        
        $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $stmt_notify->execute([$customer_user_id, $title, $message]);
        
        sendPushNotification($pdo, $customer_user_id, $title, $message);
    } catch (Exception $e) {
        error_log("Gagal mengirim notifikasi pencairan dana: " . $e->getMessage());
    }

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Pencairan dana berhasil diproses.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // Mengirimkan response code yang sesuai jika ada error otorisasi
    $code = http_response_code() >= 400 ? http_response_code() : 500;
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memproses pencairan: ' . $e->getMessage()]);
}

