<?php
// File: app/utility_get_bank_branches.php
// Penjelasan: Mengambil daftar cabang bank dari database.

require_once 'config.php';

try {
    $stmt = $pdo->query("SELECT branch_name, address, latitude, longitude, type, operational_hours FROM bank_branches WHERE is_active = 1");
    $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $branches]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data cabang.']);
}
?>
