<?php
// File: app/admin_send_direct_message.php
// Penjelasan: Admin memulai percakapan dengan nasabah.

require_once 'auth_middleware.php';

$allowed_roles = [1, 2, 3, 6]; // CS dan Manajer
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$recipient_id = $input['recipient_id'] ?? null; // ID Nasabah
$message = $input['message'] ?? '';

if (!$recipient_id || empty($message)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Penerima dan pesan wajib diisi.']);
    exit();
}

try {
    // Menggunakan sistem secure_messages yang sudah ada
    // ID Thread bisa dibuat berdasarkan kombinasi ID admin dan nasabah
    $thread_id = min($authenticated_user_id, $recipient_id) . '_' . max($authenticated_user_id, $recipient_id);

    $stmt = $pdo->prepare("INSERT INTO secure_messages (thread_id, sender_id, recipient_id, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$thread_id, $authenticated_user_id, $recipient_id, $message]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Pesan berhasil dikirim.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim pesan.']);
}
?>
