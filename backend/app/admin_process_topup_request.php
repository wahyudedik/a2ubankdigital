<?php
// File: app/admin_process_topup_request.php
// Deskripsi: Perbaikan fatal error dengan menambahkan include helper yang hilang.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/notification_helper.php';
require_once __DIR__ . '/helpers/push_notification_helper.php';
// --- PERBAIKAN KRUSIAL: Menambahkan include untuk hierarchy_helper.php ---
require_once __DIR__ . '/helpers/hierarchy_helper.php';

// Teller ke atas bisa memproses
if ($authenticated_user_role_id > 6) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$request_id = $input['request_id'] ?? 0;
$action = $input['action'] ?? ''; // 'APPROVE' atau 'REJECT'
$rejection_reason = $input['rejection_reason'] ?? null;

if ($request_id <= 0 || !in_array($action, ['APPROVE', 'REJECT'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Input tidak valid.']);
    exit();
}
if ($action === 'REJECT' && empty($rejection_reason)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Alasan penolakan wajib diisi.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Kunci dan ambil detail permintaan
    $stmt_req = $pdo->prepare("SELECT * FROM topup_requests WHERE id = ? AND status = 'PENDING' FOR UPDATE");
    $stmt_req->execute([$request_id]);
    $request = $stmt_req->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception("Permintaan tidak ditemukan atau sudah diproses.");
    }
    
    $customer_user_id = $request['user_id'];
    $amount = (float)$request['amount'];
    $new_status = ($action === 'APPROVE') ? 'APPROVED' : 'REJECTED';

    // 2. Jika disetujui, lakukan proses transaksi
    if ($action === 'APPROVE') {
        $stmt_acc = $pdo->prepare("SELECT id FROM accounts WHERE user_id = ? AND account_type = 'TABUNGAN' AND status = 'ACTIVE' FOR UPDATE");
        $stmt_acc->execute([$customer_user_id]);
        $account_id = $stmt_acc->fetchColumn();
        if (!$account_id) {
            throw new Exception("Rekening tabungan nasabah tidak ditemukan atau tidak aktif.");
        }

        $stmt_credit = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
        $stmt_credit->execute([$amount, $account_id]);

        // Generate transaction code
        $transaction_code = 'TRX' . date('YmdHis') . rand(1000, 9999);
        
        $desc = "Isi saldo via " . $request['payment_method'];
        $stmt_trx = $pdo->prepare(
            "INSERT INTO transactions (transaction_code, to_account_id, transaction_type, amount, description, status) 
             VALUES (?, ?, 'SETOR_TUNAI', ?, ?, 'SUCCESS')"
        );
        $stmt_trx->execute([$transaction_code, $account_id, $amount, $desc]);
        $transaction_id = $pdo->lastInsertId();

        $audit_details = json_encode(['topup_request_id' => $request_id, 'transaction_id' => $transaction_id]);
        $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'APPROVE_TOPUP', ?, ?)");
        $stmt_audit->execute([$authenticated_user_id, $audit_details, $_SERVER['REMOTE_ADDR']]);
    }

    // 3. Update status permintaan top-up
    $stmt_update = $pdo->prepare(
        "UPDATE topup_requests SET status = ?, processed_by = ?, processed_at = NOW(), rejection_reason = ? WHERE id = ?"
    );
    $stmt_update->execute([$new_status, $authenticated_user_id, $rejection_reason, $request_id]);

    $pdo->commit();

    // 4. Kirim notifikasi ke nasabah
    if ($action === 'APPROVE') {
        $title = "Isi Saldo Berhasil";
        $message = "Permintaan isi saldo Anda sebesar " . number_format($amount, 0, ',', '.') . " telah disetujui dan dana sudah masuk ke rekening Anda.";
    } else {
        $title = "Isi Saldo Ditolak";
        $message = "Permintaan isi saldo Anda ditolak. Alasan: " . $rejection_reason;
    }
    $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $stmt_notify->execute([$customer_user_id, $title, $message]);
    sendPushNotification($pdo, $customer_user_id, $title, $message);
    
    // 5. Kirim notifikasi ke staf atasan (jika disetujui)
    if ($action === 'APPROVE') {
        $title_staff = "Persetujuan Isi Saldo";
        
        $stmt_user_name = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt_user_name->execute([$customer_user_id]);
        $customer_name = $stmt_user_name->fetchColumn();

        $message_staff = "Persetujuan isi saldo untuk nasabah " . $customer_name . " sebesar " . number_format($amount, 0, ',', '.') . " telah diproses oleh staf.";
        
        $superiors = get_supervisor_ids($pdo, $authenticated_user_id);
        
        if ($superiors['unit_head_id']) {
             $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)")->execute([$superiors['unit_head_id'], $title_staff, $message_staff]);
        }
        if ($superiors['branch_head_id']) {
             $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)")->execute([$superiors['branch_head_id'], $title_staff, $message_staff]);
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Permintaan berhasil diproses.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memproses permintaan: ' . $e->getMessage()]);
}

