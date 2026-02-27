<?php
// File: app/utility_get_payment_methods.php
// Penjelasan: Mengambil metode pembayaran (QRIS & No. Rek) dari pengaturan.
// Endpoint ini tidak memerlukan otentikasi agar bisa diakses dari berbagai tempat.

require_once 'config.php';

try {
    // Ambil semua konfigurasi yang relevan dengan pembayaran
    $stmt = $pdo->query("SELECT config_key, config_value FROM system_configurations WHERE config_key LIKE 'payment_%'");
    $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Siapkan data dengan format yang rapi untuk frontend
    $payment_methods = [
        'qris_image_url' => $configs['payment_qris_image_url'] ?? null,
        // Pastikan bank_accounts selalu berupa array, bahkan jika kosong atau tidak ada
        'bank_accounts' => json_decode($configs['payment_bank_accounts'] ?? '[]', true)
    ];

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $payment_methods]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil metode pembayaran.']);
}

