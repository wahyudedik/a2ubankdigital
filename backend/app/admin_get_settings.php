<?php
// File: app/admin_get_settings.php
// Penjelasan: Endpoint untuk mengambil konfigurasi sistem. HANYA SUPER ADMIN.

require_once 'auth_middleware.php';

// Keamanan: Pastikan hanya Super Admin (role_id = 1) yang bisa mengakses.
if ($authenticated_user_role_id !== 1) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

try {
    // Ambil semua konfigurasi dari database
    $stmt = $pdo->query("SELECT config_key, config_value FROM system_configurations");
    $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $configs]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil pengaturan sistem.']);
}
