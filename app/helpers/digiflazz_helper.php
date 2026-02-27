<?php
// File: app/helpers/digiflazz_helper.php
// REVISI TOTAL: Memperbaiki logika pembuatan signature agar sesuai dengan endpoint yang dipanggil (pricelist vs transaction).

/**
 * Fungsi terpusat untuk berkomunikasi dengan API Digiflazz.
 * Secara otomatis membuat signature yang benar berdasarkan jenis permintaan.
 *
 * @param array $body Body dari request (misal: ['cmd' => 'pricelist'] atau data transaksi).
 * @return array Hasil respons dari API.
 * @throws Exception Jika terjadi kesalahan.
 */
function callDigiflazzApi($body = []) {
    $baseUrl = $_ENV['DIGIFLAZZ_API_BASE_URL'];
    $username = $_ENV['DIGIFLAZZ_USERNAME'];
    $apiKey = $_ENV['DIGIFLAZZ_API_KEY'];       // Development Key
    $productionKey = $_ENV['DIGIFLAZZ_PRODUCTION_KEY']; // Production Key
    $appEnv = $_ENV['APP_ENV'] ?? 'development';

    $signature = '';
    $endpoint = '';

    // --- LOGIKA BARU: Tentukan signature dan endpoint berdasarkan isi body ---
    if (isset($body['cmd']) && $body['cmd'] === 'pricelist') {
        // Ini adalah permintaan untuk daftar harga
        $endpoint = 'price-list';
        // Signature untuk pricelist SELALU menggunakan Development Key (apiKey) + string "pricelist"
        $signature = md5($username . $apiKey . "pricelist");
    } 
    elseif (isset($body['ref_id'])) {
        // Ini adalah permintaan transaksi (inquiry atau payment)
        $endpoint = 'transaction';
        // Pilih kunci yang sesuai: production key jika env production, jika tidak, pakai api key (dev)
        $activeKey = ($appEnv === 'production' && !empty($productionKey)) ? $productionKey : $apiKey;
        $signature = md5($username . $activeKey . $body['ref_id']);
    } 
    else {
        throw new Exception("Perintah API Digiflazz tidak valid atau tidak dikenali.");
    }
    // --- AKHIR LOGIKA SIGNATURE ---

    $request_body = array_merge($body, [
        'username' => $username,
        'sign' => $signature,
    ]);

    $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
    $headers = ['Content-Type: application/json'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_body));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception("cURL Error: " . $curlError);
    }

    $decoded_response = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Gagal mem-parsing JSON dari Digiflazz. Respons mentah: " . $response);
    }
    
    return ['http_code' => $httpCode, 'body' => $decoded_response];
}

