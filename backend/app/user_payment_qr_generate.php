<?php
// File: app/user_payment_qr_generate.php
// Penjelasan: Nasabah membuat QR code untuk menerima pembayaran. Menggunakan library chillerlan/php-qrcode.

require_once 'auth_middleware.php';
// vendor/autoload.php sudah otomatis dipanggil oleh config.php, jadi tidak perlu dipanggil lagi di sini.

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

$input = json_decode(file_get_contents('php://input'), true);
$amount = $input['amount'] ?? 0;

try {
    // Ambil data nasabah (nomor rekening dan nama)
    $stmt = $pdo->prepare("
        SELECT a.account_number, u.full_name
        FROM accounts a
        JOIN users u ON a.user_id = u.id
        WHERE a.user_id = ? AND a.account_type = 'TABUNGAN'
    ");
    $stmt->execute([$authenticated_user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_data) {
        throw new Exception("Rekening tidak ditemukan.");
    }

    // Buat payload QRIS-like (simplified)
    $payload = json_encode([
        'iss' => 'a2ubankdigital.my.id',
        'acc' => $user_data['account_number'],
        'name' => $user_data['full_name'],
        'amt' => (float) $amount
    ]);

    // Opsi untuk QR code
    $options = new QROptions([
        'version' => 5,
        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel' => QRCode::ECC_L,
        'scale' => 10,
        'imageBase64' => true, // Ini akan langsung menghasilkan string base64
    ]);

    // Generate QR code
    $qrcode = new QRCode($options);
    $dataUri = $qrcode->render($payload);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => ['qr_base64' => $dataUri]]);

} catch (Exception $e) {
    http_response_code(500);
    // Tampilkan pesan error aktual untuk debugging
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuat QR Code: ' . $e->getMessage()]);
}
?>