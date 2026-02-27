<?php
// File: app/admin_cs_reply_message.php
// Penjelasan: CS membalas pesan nasabah.

require_once 'auth_middleware.php';

$allowed_roles = [6]; // Hanya CS
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$thread_id = $input['thread_id'] ?? null;
$recipient_id = $input['recipient_id'] ?? null; // ID nasabah
$message = $input['message'] ?? '';

if (!$thread_id || !$recipient_id || empty($message)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Input tidak lengkap.']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO secure_messages (thread_id, sender_id, recipient_id, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$thread_id, $authenticated_user_id, $recipient_id, $message]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Balasan berhasil terkirim.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim balasan.']);
}
?>
