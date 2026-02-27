<?php
// File: app/user_get_active_announcements.php
// Penjelasan: Nasabah mengambil daftar pengumuman yang sedang aktif.

require_once 'auth_middleware.php';

try {
    $stmt = $pdo->prepare("SELECT title, content, type, start_date, end_date FROM announcements WHERE is_active = 1 AND CURDATE() BETWEEN start_date AND end_date ORDER BY created_at DESC");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $announcements]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil pengumuman.']);
}
?>
