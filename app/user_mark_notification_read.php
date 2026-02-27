<?php
// File: app/user_mark_notification_read.php
// Penjelasan: Nasabah menandai notifikasi sebagai telah dibaca.

require_once 'auth_middleware.php';

/*
    CATATAN DATABASE: Menambahkan kolom `is_read` ke tabel `notifications`.
    ALTER TABLE `notifications` ADD `is_read` BOOLEAN NOT NULL DEFAULT FALSE AFTER `type`;
*/

$input = json_decode(file_get_contents('php://input'), true);
$notification_id = $input['notification_id'] ?? null; // Bisa berupa ID tunggal atau 'all'

if (!$notification_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Notifikasi wajib diisi.']);
    exit();
}

try {
    if ($notification_id === 'all') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
        $stmt->execute([$authenticated_user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
        $stmt->execute([(int)$notification_id, $authenticated_user_id]);
    }
    
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Notifikasi ditandai telah dibaca.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui notifikasi.']);
}
?>
