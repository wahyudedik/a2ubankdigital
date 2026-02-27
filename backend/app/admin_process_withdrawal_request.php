<?php
// File: app/admin_process_withdrawal_request.php
// Penjelasan: Admin menyetujui/menolak permintaan penarikan.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/push_notification_helper.php';

if ($authenticated_user_role_id > 5) { // Teller ke atas
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$request_id = $input['request_id'] ?? 0;
$action = $input['action'] ?? ''; // 'APPROVE' atau 'REJECT'

try {
    $pdo->beginTransaction();

    // 1. Kunci dan ambil permintaan
    $stmt_req = $pdo->prepare("SELECT * FROM withdrawal_requests WHERE id = ? AND status = 'PENDING' FOR UPDATE");
    $stmt_req->execute([$request_id]);
    $request = $stmt_req->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception("Permintaan tidak ditemukan atau sudah diproses.");
    }
    
    $customer_user_id = $request['user_id'];
    $amount = (float)$request['amount'];
    $new_status = ($action === 'APPROVE') ? 'APPROVED' : 'REJECTED';

    // 2. Jika ditolak, kembalikan dana ke nasabah dan batalkan transaksi
    if ($action === 'REJECT') {
        $stmt_acc = $pdo->prepare("SELECT id FROM accounts WHERE user_id = ? AND account_type = 'TABUNGAN'");
        $stmt_acc->execute([$customer_user_id]);
        $account_id = $stmt_acc->fetchColumn();

        $stmt_credit = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
        $stmt_credit->execute([$amount, $account_id]);
        
        // Perbarui transaksi yang sebelumnya PENDING menjadi FAILED
        $stmt_fail_trx = $pdo->prepare(
            "UPDATE transactions SET status = 'FAILED' 
             WHERE from_account_id = ? AND transaction_type = 'TARIK_TUNAI' AND status = 'PENDING' AND amount = ? 
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt_fail_trx->execute([$account_id, $amount]);
    }
    
    // 3. Update status permintaan
    $stmt_update = $pdo->prepare(
        "UPDATE withdrawal_requests SET status = ?, processed_by = ?, processed_at = NOW() WHERE id = ?"
    );
    $stmt_update->execute([$new_status, $authenticated_user_id, $request_id]);

    $pdo->commit();

    // 4. Kirim notifikasi ke nasabah
    if ($action === 'APPROVE') {
        $title = "Permintaan Penarikan Disetujui";
        $message = "Permintaan penarikan dana Anda sebesar " . number_format($amount, 0, ',', '.') . " telah disetujui dan akan segera diproses.";
    } else {
        $title = "Penarikan Dana Ditolak";
        $message = "Mohon maaf, permintaan penarikan dana Anda ditolak. Dana telah dikembalikan ke saldo Anda.";
    }
    $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $stmt_notify->execute([$customer_user_id, $title, $message]);
    sendPushNotification($pdo, $customer_user_id, $title, $message);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Permintaan berhasil diproses.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memproses permintaan: ' . $e->getMessage()]);
}

