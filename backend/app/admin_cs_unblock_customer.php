<?php
// File: app/admin_cs_unblock_customer.php
// Penjelasan: Staf (CS) membuka blokir akun nasabah.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: CS, Kepala Unit, Kepala Cabang, Super Admin
$allowed_roles = [1, 2, 3, 6];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$customer_id = $input['customer_id'] ?? null;

if (!$customer_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Nasabah wajib diisi.']);
    exit();
}

/*
    CATATAN DATABASE: Memerlukan tabel `user_security`.
    CREATE TABLE `user_security` (
      `user_id` int(10) UNSIGNED NOT NULL,
      `failed_login_attempts` tinyint(4) NOT NULL DEFAULT 0,
      `failed_pin_attempts` tinyint(4) NOT NULL DEFAULT 0,
      `last_failed_attempt_at` timestamp NULL,
      PRIMARY KEY (`user_id`),
      CONSTRAINT `fk_sec_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;
*/

try {
    $pdo->beginTransaction();
    // 1. Ubah status user
    $stmt_user = $pdo->prepare("UPDATE users SET status = 'ACTIVE' WHERE id = ? AND role_id = 9 AND status = 'BLOCKED'");
    $stmt_user->execute([$customer_id]);
    
    if ($stmt_user->rowCount() === 0) {
        throw new Exception("Nasabah tidak ditemukan atau statusnya tidak diblokir.");
    }
    
    // 2. Reset counter percobaan gagal
    $stmt_sec = $pdo->prepare("
        INSERT INTO user_security (user_id, failed_login_attempts, failed_pin_attempts) VALUES (?, 0, 0)
        ON DUPLICATE KEY UPDATE failed_login_attempts = 0, failed_pin_attempts = 0
    ");
    $stmt_sec->execute([$customer_id]);
    
    $pdo->commit();
    
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Blokir akun nasabah berhasil dibuka.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuka blokir: ' . $e->getMessage()]);
}
?>
