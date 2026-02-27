<?php
// File: app/utility_get_market_data.php
// Penjelasan: SOLUSI FINAL - Mengambil data dari Google Sheet yang dipublikasikan
// sebagai CSV. Metode ini sangat andal, cepat, dan gratis.
// Versi Produksi: Menambahkan fallback ke cache, parsing data yang lebih lengkap, dan menghapus reksa dana.

require_once 'config.php'; 

// Mengatur locale untuk memastikan parsing angka desimal (float) berjalan benar
setlocale(LC_NUMERIC, 'C');

// --- KONFIGURASI ---
$google_sheet_csv_url = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vQiNa2wP7DxUqJFLBD3jyP4yExp-ac8koa9j7T7p-dkLxahTckFvHYDgnGEcSi0v2s0QH_ao1d2LlHs/pub?gid=0&single=true&output=csv';
$cache_directory = dirname(__DIR__) . '/cache';
$cache_file = "{$cache_directory}/google_finance_data.csv";
$cache_duration = 300; // Cache selama 5 menit

function fetch_from_google_sheet($url, $cache_file, $cache_time, $cache_dir) {
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0775, true);
    }

    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
        return file_get_contents($cache_file);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $csv_data = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error || $http_code !== 200 || !$csv_data || strpos($csv_data, '<html') !== false) {
        error_log("Gagal mengambil data Google Sheet. HTTP Code: $http_code. cURL Error: $error");
        if (file_exists($cache_file)) {
            return file_get_contents($cache_file);
        }
        return ['error' => 'Layanan data pasar sedang tidak tersedia. Silakan coba lagi nanti.'];
    }
    
    if (is_writable($cache_dir)) {
        file_put_contents($cache_file, $csv_data);
    }
    
    return $csv_data;
}

// --- PROSES UTAMA ---
$csv_content = fetch_from_google_sheet($google_sheet_csv_url, $cache_file, $cache_duration, $cache_directory);
$processed_stocks = [];

if (is_array($csv_content) && isset($csv_content['error'])) {
    $processed_stocks[] = ['name' => $csv_content['error'], 'price_new' => 0, 'change' => 0, 'change_percent' => 0, 'volume' => 0];
} else {
    $rows = array_map('str_getcsv', explode("\n", trim($csv_content)));
    $header = array_shift($rows);

    if (count($rows) > 0 && count($header) >= 6) {
        foreach ($rows as $row) {
            if (count($row) >= 6 && !empty($row[0])) {
                $code = str_replace('IDX:', '', $row[0]);
                $name = $row[1] ?? $code; 

                // [PERBAIKAN] Mengganti koma desimal (jika ada) dengan titik sebelum konversi ke float
                $change_percent_str = str_replace(',', '.', $row[4] ?? '0');

                $processed_stocks[] = [
                    'code' => $code,
                    'name' => $name,
                    'price_new' => (float)str_replace(',', '', $row[2] ?? 0), 
                    'change' => (float)($row[3] ?? 0),
                    'change_percent' => (float)($change_percent_str),
                    'volume' => (int)($row[5] ?? 0)
                ];
            }
        }
    } else {
         $processed_stocks[] = ['name' => 'Format data CSV tidak sesuai atau kosong.', 'price_new' => 0, 'change' => 0, 'change_percent' => 0, 'volume' => 0];
    }
}

// --- FINAL RESPONSE ---
$response = [
    'status' => 'success',
    'data' => [
        'stocks' => $processed_stocks
    ],
    'last_updated' => date('c', file_exists($cache_file) ? filemtime($cache_file) : time())
];

http_response_code(200);
echo json_encode($response);
?>

