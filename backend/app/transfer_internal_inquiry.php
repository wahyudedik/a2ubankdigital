<?php
// File: app/transfer_internal_inquiry.php
// Penjelasan: Memvalidasi rekening tujuan dan mengembalikan nama pemilik.

require_once 'auth_middleware.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['destination_account_number'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nomor rekening tujuan wajib diisi.']);
    exit();
}

$destination_account_number = $input['destination_account_number'];

try {
    // Ambil info rekening tujuan
    $stmt = $pdo->prepare("
        SELECT a.id, a.user_id, u.full_name
        FROM accounts a
        JOIN users u ON a.user_id = u.id
        WHERE a.account_number = ? AND a.status = 'ACTIVE'
    ");
    $stmt->execute([$destination_account_number]);
    $destination = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$destination) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Nomor rekening tujuan tidak ditemukan atau tidak aktif.']);
        exit();
    }

    // Cek agar tidak transfer ke diri sendiri
    if ($destination['user_id'] == $authenticated_user_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Anda tidak dapat mentransfer ke rekening Anda sendiri.']);
        exit();
    }

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'data' => [
            'account_number' => $destination_account_number,
            'recipient_name' => $destination['full_name']
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
}

?>
