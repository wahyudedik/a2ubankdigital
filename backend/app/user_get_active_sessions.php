<?php
// File: app/user_get_active_sessions.php
// Penjelasan: Nasabah melihat daftar sesi login yang aktif.

require_once 'auth_middleware.php';


try {
    // Ambil semua sesi untuk user ini
    $stmt = $pdo->prepare("SELECT id, ip_address, user_agent, last_activity FROM user_sessions WHERE user_id = ? ORDER BY last_activity DESC");
    $stmt->execute([$authenticated_user_id]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $sessions]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data sesi.']);
}
?>
