<?php
// File: app/bill_payment_inquiry.php
// REVISI: Disesuaikan untuk menerima product_code dan customer_no

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/digiflazz_helper.php';
require_once __DIR__ . '/helpers/log_helper.php';

$input = json_decode(file_get_contents('php://input'), true);
$product_code = $input['product_code'] ?? null;
$customer_no = $input['customer_no'] ?? null;

if (empty($product_code) || empty($customer_no)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Kode produk dan nomor pelanggan wajib diisi.']);
    exit();
}

try {
    // Untuk inquiry (cek tagihan), kita gunakan endpoint 'check-bill'
    $ref_id = "INQ-" . time() . "-" . $input['customer_no'];
    $payload = [
        "commands" => "inq-pasca",
        "buyer_sku_code" => $product_code,
        "customer_no" => $customer_no,
        "ref_id" => $ref_id
    ];

    $response = callDigiflazzApi('transaction', 'POST', $payload);

    // Logging untuk diagnosis
    log_system_event($pdo, 'INFO', 'Digiflazz Inquiry Response', ['request' => $payload, 'response' => $response['body']]);
    
    if ($response['http_code'] === 200 && isset($response['body']['data']['status']) && $response['body']['data']['status'] === 'Sukses') {
        $inquiry_data = $response['body']['data'];
        http_response_code(200);
        echo json_encode([
            'status' => 'success', 
            'data' => [
                'ref_id' => $ref_id,
                'product_name' => $inquiry_data['product_name'],
                'customer_name' => $inquiry_data['customer_name'] ?? 'N/A',
                'customer_no' => $inquiry_data['customer_no'],
                'selling_price' => (float)($inquiry_data['selling_price'] ?? 0),
                'admin_fee' => 0, // Digiflazz sudah memasukkan admin ke selling_price
                'total_amount' => (float)($inquiry_data['selling_price'] ?? 0)
            ]
        ]);
    } else {
        $error_message = $response['body']['data']['message'] ?? 'Gagal melakukan inquiry tagihan.';
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $error_message]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()]);
}
