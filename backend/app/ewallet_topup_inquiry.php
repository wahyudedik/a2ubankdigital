<?php
// File: app/ewallet_topup_inquiry.php
// Penjelasan: Memvalidasi nomor telepon tujuan e-wallet dan mengembalikan nama pemilik.

require_once 'auth_middleware.php';

$input = json_decode(file_get_contents('php://input'), true);

$biller_code = $input['biller_code'] ?? ''; // e.g., GOPAY, OVO, DANA
$phone_number = $input['phone_number'] ?? '';

if (empty($biller_code) || empty($phone_number)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Kode E-Wallet dan Nomor Telepon wajib diisi.']);
    exit();
}

try {
    // --- SIMULASI PANGGILAN API KE AGGREGATOR E-WALLET ---
    // Di aplikasi nyata, Anda akan memanggil API pihak ketiga di sini.
    $mock_responses = [
        'GOPAY' => ['081234567890' => 'Ahmad Subarjo'],
        'OVO'   => ['087712345678' => 'Siti Lestari'],
        'DANA'  => ['085987654321' => 'Putu Wijaya']
    ];

    $customer_name = $mock_responses[$biller_code][$phone_number] ?? null;

    if ($customer_name) {
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'data' => [
                'inquiry_id' => 'EWINQ' . time(),
                'biller_code' => $biller_code,
                'phone_number' => $phone_number,
                'customer_name' => $customer_name
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Nomor telepon tidak terdaftar pada layanan ' . $biller_code]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat inquiry.']);
}
?>
