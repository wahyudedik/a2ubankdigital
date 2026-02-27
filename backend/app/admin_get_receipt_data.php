<?php
// File: app/admin_get_receipt_data.php
// Penjelasan: Endpoint baru untuk mengambil data nota transaksi.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/receipt_helper.php';

// Semua staf yang bisa melakukan transaksi teller bisa mengakses
if ($authenticated_user_role_id > 6) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$transaction_id = $_GET['id'] ?? null;

if (!$transaction_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Transaksi wajib diisi.']);
    exit();
}

try {
    $receiptData = getReceiptData($pdo, $transaction_id);

    if ($receiptData) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'data' => $receiptData]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Data transaksi tidak ditemukan.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data nota: ' . $e->getMessage()]);
}
