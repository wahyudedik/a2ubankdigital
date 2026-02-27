<?php
// File: app/notifications_get_list.php
// Penjelasan: Mengambil daftar notifikasi untuk pengguna yang login.

require_once 'auth_middleware.php';

try {
    // PERBAIKAN: Mengubah nama tabel dari 'notification' menjadi 'notifications'
    $stmt = $pdo->prepare("SELECT id, title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$authenticated_user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $notifications]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil notifikasi: ' . $e->getMessage()]);
}
?>
