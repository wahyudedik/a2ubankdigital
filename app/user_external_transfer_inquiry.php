<?php
// File: app/user_external_transfer_inquiry.php
// Penjelasan: Nasabah mengecek rekening di bank lain (simulasi).

require_once 'auth_middleware.php';

$input = json_decode(file_get_contents('php://input'), true);
$bank_code = $input['bank_code'] ?? '';
$account_number = $input['account_number'] ?? '';

if (empty($bank_code) || empty($account_number)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Kode bank dan nomor rekening wajib diisi.']);
    exit();
}

// --- SIMULASI API PIHAK KETIGA ---
// Di dunia nyata, ini akan menjadi panggilan cURL/Guzzle ke API switching.
function mock_interbank_inquiry($code, $acc_num) {
    $mock_data = [
        '002' => ['1234567890' => 'Budi Santoso'],
        '008' => ['0987654321' => 'Citra Lestari'],
        '009' => ['1122334455' => 'Dewi Anggraini']
    ];
    if (isset($mock_data[$code]) && isset($mock_data[$code][$acc_num])) {
        return ['status' => 'success', 'recipient_name' => $mock_data[$code][$acc_num]];
    }
    return ['status' => 'error', 'message' => 'Rekening tidak ditemukan.'];
}
// --- AKHIR SIMULASI ---

$result = mock_interbank_inquiry($bank_code, $account_number);

if ($result['status'] === 'success') {
    http_response_code(200);
    echo json_encode($result);
} else {
    http_response_code(404);
    echo json_encode($result);
}
?>
