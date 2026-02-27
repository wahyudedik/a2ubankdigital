<?php
// File: app/webhooks/digiflazz_callback.php
// Penjelasan: Menerima pembaruan status transaksi dari Digiflazz.

// Sertakan file konfigurasi dan helper yang diperlukan
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers/log_helper.php';
require_once __DIR__ . '/../helpers/push_notification_helper.php';

// Ambil data mentah dari request body
$raw_payload = file_get_contents('php://input');
// Ambil signature dari header
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';

// Validasi signature untuk keamanan
$webhook_secret = $_ENV['DIGIFLAZZ_WEBHOOK_SECRET'];
$expected_signature = 'sha1=' . hash_hmac('sha1', $raw_payload, $webhook_secret);

if (!hash_equals($expected_signature, $signature)) {
    log_system_event($pdo, 'WARNING', 'Invalid Digiflazz webhook signature.', ['received_signature' => $signature]);
    http_response_code(403);
    die('Invalid signature.');
}

$payload = json_decode($raw_payload, true);
$data = $payload['data'] ?? null;

if (!$data) {
    http_response_code(400);
    die('Invalid payload.');
}

// Log setiap callback yang valid untuk audit
log_system_event($pdo, 'INFO', 'Digiflazz Webhook Received', $data);

$ref_id = $data['ref_id'] ?? null;
$status = strtolower($data['status'] ?? ''); // Sukses, Gagal, Pending

if (!$ref_id || !$status) {
    http_response_code(400);
    die('Missing required data.');
}

try {
    $pdo->beginTransaction();

    // Cari transaksi di database kita berdasarkan ref_id
    $stmt_trx = $pdo->prepare("SELECT * FROM transactions WHERE id = ? FOR UPDATE");
    $stmt_trx->execute([$ref_id]);
    $transaction = $stmt_trx->fetch();

    if ($transaction) {
        $new_status = 'PENDING';
        if ($status === 'sukses') {
            $new_status = 'SUCCESS';
        } elseif ($status === 'gagal') {
            $new_status = 'FAILED';
        }

        // Update status transaksi kita
        $stmt_update = $pdo->prepare("UPDATE transactions SET status = ?, description = CONCAT(description, ' | SN: ', ?) WHERE id = ?");
        $stmt_update->execute([$new_status, $data['sn'] ?? 'N/A', $ref_id]);

        // Jika transaksi GAGAL, kembalikan dana ke nasabah
        if ($new_status === 'FAILED' && $transaction['status'] !== 'FAILED') {
            $stmt_refund = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
            $total_refund = (float)$transaction['amount'] + (float)$transaction['fee'];
            $stmt_refund->execute([$total_refund, $transaction['from_account_id']]);
        }
        
        // Kirim notifikasi ke nasabah
        $stmt_user = $pdo->prepare("SELECT user_id FROM accounts WHERE id = ?");
        $stmt_user->execute([$transaction['from_account_id']]);
        $user_id = $stmt_user->fetchColumn();

        if ($user_id) {
            $title = "Update Pembayaran Tagihan";
            $message = "Pembayaran Anda untuk {$transaction['description']} telah " . ($new_status === 'SUCCESS' ? 'berhasil.' : 'gagal.');
            $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)")->execute([$user_id, $title, $message]);
            sendPushNotification($pdo, $user_id, $title, $message);
        }
    }
    
    $pdo->commit();
    http_response_code(200);
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_system_event($pdo, 'ERROR', 'Digiflazz Webhook Processing Error', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error.']);
}
