<?php
// File: app/utility_get_system_status.php
// Penjelasan: Endpoint publik untuk monitoring kesehatan API.

require_once 'config.php';

$status = [
    'api' => 'operational',
    'database' => 'operational',
    'timestamp' => date('c')
];
$http_code = 200;

// Cek koneksi database
try {
    $pdo->query("SELECT 1");
} catch (PDOException $e) {
    $status['database'] = 'degraded';
    $http_code = 503; // Service Unavailable
}

// Cek layanan lain jika ada (misal: payment gateway)
// ...

http_response_code($http_code);
header('Content-Type: application/json');
echo json_encode($status);
?>
