<?php
// File: app/user_request_credit_limit_increase.php
// Penjelasan: Nasabah mengajukan kenaikan limit kartu kredit.

require_once 'auth_middleware.php';

/*
    CATATAN DATABASE: Memerlukan tabel `limit_increase_requests`.
    CREATE TABLE `limit_increase_requests` (
      `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id` int(10) UNSIGNED NOT NULL,
      `account_id` int(10) UNSIGNED NOT NULL, -- Credit Card Account
      `current_limit` decimal(20,2) NOT NULL,
      `requested_limit` decimal(20,2) NOT NULL,
      `reason` text,
      `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
      `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB;
*/

$input = json_decode(file_get_contents('php://input'), true);
// Validasi input
// ...

try {
    // Ambil akun kartu kredit dan limit saat ini
    // ...

    $sql = "INSERT INTO limit_increase_requests (user_id, account_id, current_limit, requested_limit, reason) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $authenticated_user_id, $input['account_id'], $input['current_limit'], $input['requested_limit'], $input['reason'] ?? ''
    ]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Pengajuan kenaikan limit berhasil dikirim.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim pengajuan.']);
}
?>
