<?php
// File: app/user_payment_qr_scan_info.php
// Penjelasan: Mendapatkan info penerima dari hasil scan QR code.

require_once 'auth_middleware.php';

$input = json_decode(file_get_contents('php://input'), true);
$qr_payload = $input['qr_payload'] ?? '';
if (empty($qr_payload)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Payload QR wajib diisi.']);
    exit();
}

try {
    $data = json_decode($qr_payload, true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($data['acc'])) {
        throw new Exception("Format QR tidak valid.");
    }
    
    // Cek info penerima di DB. Sama seperti transfer inquiry.
    $stmt = $pdo->prepare("SELECT u.full_name FROM users u JOIN accounts a ON u.id = a.user_id WHERE a.account_number = ? AND a.account_type = 'TABUNGAN'");
    $stmt->execute([$data['acc']]);
    $recipient_name = $stmt->fetchColumn();

    if (!$recipient_name) {
        throw new Exception("Penerima tidak ditemukan.");
    }
    
    $response = [
        'account_number' => $data['acc'],
        'recipient_name' => $recipient_name,
        'amount' => $data['amt'] ?? 0
    ];

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $response]);

} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memproses QR: ' . $e->getMessage()]);
}
?>
