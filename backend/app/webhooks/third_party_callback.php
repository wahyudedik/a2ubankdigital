<?php
// File: app/webhooks/third_party_callback.php
// Penjelasan: Menerima callback dari sistem pihak ketiga (misal: payment gateway).

// Tidak menggunakan auth_middleware, otentikasi menggunakan cara lain (API key, signature)
require_once '../config.php';

// Ambil signature dari header
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
$raw_payload = file_get_contents('php://input');

// Validasi signature (ini contoh, sesuaikan dengan provider)
$expected_signature = hash_hmac('sha256', $raw_payload, $_ENV['THIRD_PARTY_WEBHOOK_SECRET']);
if (!hash_equals($expected_signature, $signature)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid signature.']);
    exit();
}

$payload = json_decode($raw_payload, true);
$transaction_id = $payload['transaction_id'] ?? null;
$status = $payload['status'] ?? null; // 'SUCCESS' atau 'FAILED'

if (!$transaction_id || !$status) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Payload tidak lengkap.']);
    exit();
}

try {
    // Update status transaksi di database kita berdasarkan callback
    $stmt = $pdo->prepare("UPDATE transactions SET status = ? WHERE id = ? AND status = 'PENDING'");
    $stmt->execute([$status, $transaction_id]);

    if ($stmt->rowCount() > 0) {
        // Jika sukses, lakukan aksi lanjutan (misal: kirim notifikasi ke user)
        if ($status === 'SUCCESS') {
            // ... logika tambahan
        }
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Callback diterima.']);
    } else {
        // Mungkin transaksi sudah diproses atau tidak ditemukan
        http_response_code(202); // Accepted, tapi tidak ada aksi
        echo json_encode(['status' => 'ignored', 'message' => 'Transaksi tidak ditemukan atau sudah diproses.']);
    }

} catch (PDOException $e) {
    // Log error, tapi kirim response 500 agar pihak ketiga mencoba lagi
    error_log("Webhook Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error.']);
}
?>
