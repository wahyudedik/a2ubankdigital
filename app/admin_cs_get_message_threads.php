<?php
// File: app/admin_cs_get_message_threads.php
// Penjelasan: CS melihat daftar percakapan dari nasabah.

require_once 'auth_middleware.php';

$allowed_roles = [6]; // Hanya CS
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

try {
    // Ambil pesan terakhir dari setiap thread
    $sql = "
        SELECT u.full_name, m1.*
        FROM secure_messages m1
        JOIN (
            SELECT thread_id, MAX(sent_at) as max_sent_at
            FROM secure_messages
            GROUP BY thread_id
        ) m2 ON m1.thread_id = m2.thread_id AND m1.sent_at = m2.max_sent_at
        JOIN users u ON m1.sender_id = u.id OR m1.recipient_id = u.id
        WHERE u.role_id = 9
        ORDER BY m1.sent_at DESC
    ";
    
    $stmt = $pdo->query($sql);
    $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $threads]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil percakapan: ' . $e->getMessage()]);
}
?>
