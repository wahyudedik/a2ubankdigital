<?php
// File: app/utility_get_public_config.php
// Penjelasan: Endpoint publik untuk mengambil konfigurasi yang aman untuk ditampilkan di halaman publik, seperti link download aplikasi.

require_once 'config.php'; // Hanya butuh koneksi DB, bukan auth

try {
    // Ambil hanya kunci konfigurasi yang spesifik dan aman untuk publik
    $stmt = $pdo->query("
        SELECT config_key, config_value 
        FROM system_configurations 
        WHERE config_key IN ('APP_DOWNLOAD_LINK_IOS', 'APP_DOWNLOAD_LINK_ANDROID')
    ");
    $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Siapkan data dengan nilai default jika tidak ada di database
    $public_config = [
        'app_download_link_ios' => $configs['APP_DOWNLOAD_LINK_IOS'] ?? '#',
        'app_download_link_android' => $configs['APP_DOWNLOAD_LINK_ANDROID'] ?? '#'
    ];

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $public_config]);

} catch (PDOException $e) {
    http_response_code(500);
    // Kirim error generik ke publik
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil konfigurasi.']);
}
?>
