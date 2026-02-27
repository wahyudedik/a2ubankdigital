<?php
// File: app/utility_get_billers.php
// REVISI: Disederhanakan untuk menggunakan helper baru dan penanganan error yang lebih baik.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/digiflazz_helper.php';
require_once __DIR__ . '/helpers/log_helper.php';

try {
    // Memanggil API daftar harga dengan body yang benar
    $response = callDigiflazzApi(['cmd' => 'pricelist']);
    
    // Log respons untuk debugging
    log_system_event($pdo, 'INFO', 'Digiflazz Get Pricelist Response', $response['body']);
    
    $data = $response['body']['data'] ?? [];

    // Validasi respons yang diterima
    if ($response['http_code'] !== 200 || !is_array($data) || isset($data['rc'])) {
        $error_message = $data['message'] ?? 'Gagal mengambil daftar produk dari Digiflazz. Periksa kembali konfigurasi API Key Anda.';
        throw new Exception($error_message);
    }

    // Mapping data agar konsisten dengan frontend
    $products = array_map(function($p) {
        return [
            'buyer_sku_code' => $p['buyer_sku_code'],
            'product_name'   => $p['product_name'],
            'category'       => $p['category'],
            'brand'          => $p['brand'],
            'type'           => strtolower($p['type']), // prabayar/pascabayar
            'price'          => $p['price'] ?? 0,
            'desc'           => $p['desc'] ?? '',
        ];
    }, $data);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $products]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()]);
}

