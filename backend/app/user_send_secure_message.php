<?php
// File: app/user_send_secure_message.php
// Penjelasan: Nasabah mengirim pesan aman ke CS.

require_once 'auth_middleware.php';

/*
    CATATAN DATABASE: Memerlukan tabel `secure_messages`.
    CREATE TABLE `secure_messages` (
      `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `thread_id` int(10) UNSIGNED NOT NULL,
      `sender_id` int(10) UNSIGNED NOT NULL,
      `recipient_id` int(10) UNSIGNED NOT NULL, -- 0 untuk CS
      `message` text NOT NULL,
      `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `is_read` tinyint(1) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`), KEY `thread_id` (`thread_id`)
    ) ENGINE=InnoDB;
*/

$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';
$thread_id = $input['thread_id'] ?? null; // Null jika pesan baru

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Pesan tidak boleh kosong.']);
    exit();
}

try {
    $pdo->beginTransaction();

    if (!$thread_id) {
        // Pesan baru, buat thread baru
        // Di sistem nyata, thread bisa dibuat lebih kompleks
        $thread_id = time() . $authenticated_user_id;
    }
    
    $stmt = $pdo->prepare("INSERT INTO secure_messages (thread_id, sender_id, recipient_id, message) VALUES (?, ?, 0, ?)");
    $stmt->execute([$thread_id, $authenticated_user_id, $message]);
    
    $pdo->commit();
    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Pesan berhasil terkirim.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim pesan.']);
}
?>
