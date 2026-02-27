<?php
// File: app/admin_config_update.php
// Penjelasan: Endpoint untuk memperbarui konfigurasi sistem.
// REVISI: Menambahkan pencatatan ke log audit.

require_once 'auth_middleware.php';

if ($authenticated_user_role_id !== 1) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input) || !is_array($input)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Input tidak valid.']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        INSERT INTO system_configurations (config_key, config_value) 
        VALUES (:key, :value)
        ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
    ");

    foreach ($input as $key => $value) {
        $stmt->execute([':key' => $key, ':value' => $value]);
    }

    // --- PENAMBAHAN LOG AUDIT ---
    $audit_details = json_encode(['updated_keys' => array_keys($input)]);
    $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'UPDATE_SYSTEM_CONFIG', ?, ?)");
    $stmt_audit->execute([$authenticated_user_id, $audit_details, $_SERVER['REMOTE_ADDR']]);
    // --- AKHIR PENAMBAHAN ---
    
    $pdo->commit();
    
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Pengaturan sistem berhasil diperbarui.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui pengaturan: ' . $e->getMessage()]);
}
