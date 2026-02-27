<?php
// File: app/user_get_security_activity.php
// Penjelasan: Nasabah melihat log aktivitas keamanan akunnya.

require_once 'auth_middleware.php';

// Aktivitas ini akan dicatat menggunakan log_helper.php
// Contoh pemanggilan di file lain:
// log_system_event($pdo, 'INFO', 'Password change successful', ['user_id' => $user_id, 'ip_address' => $_SERVER['REMOTE_ADDR']]);

try {
    $stmt = $pdo->prepare("SELECT message, context->>'$.ip_address' as ip_address, created_at FROM system_logs WHERE context->>'$.user_id' = ? AND message LIKE '%successful' ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$authenticated_user_id]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $activities]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil riwayat aktivitas.']);
}
?>
