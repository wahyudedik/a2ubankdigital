<?php
// File: app/user_request_account_closure.php
// Penjelasan: Nasabah mengajukan permohonan penutupan akun.

require_once 'auth_middleware.php';

/*
    CATATAN DATABASE: Memerlukan tabel `account_closure_requests`.
    CREATE TABLE `account_closure_requests` (
      `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id` int(10) UNSIGNED NOT NULL,
      `reason` text NOT NULL,
      `status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
      `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `processed_by_staff_id` int(10) UNSIGNED DEFAULT NULL,
      `processed_at` timestamp NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_user_pending` (`user_id`, `status`)
    ) ENGINE=InnoDB;
*/

$input = json_decode(file_get_contents('php://input'), true);
$reason = $input['reason'] ?? '';
if (empty($reason)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Alasan penutupan wajib diisi.']);
    exit();
}

try {
    // Cek apakah ada pinjaman/deposito aktif
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE user_id = ? AND account_type IN ('PINJAMAN', 'DEPOSITO') AND status = 'ACTIVE'");
    $stmt_check->execute([$authenticated_user_id]);
    if ($stmt_check->fetchColumn() > 0) {
        throw new Exception("Anda tidak dapat menutup akun karena masih memiliki pinjaman atau deposito aktif.");
    }
    
    $stmt_insert = $pdo->prepare("INSERT INTO account_closure_requests (user_id, reason) VALUES (?, ?)");
    $stmt_insert->execute([$authenticated_user_id, $reason]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Permohonan penutupan akun Anda telah diterima dan akan ditinjau oleh tim kami.']);

} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) { // Kode error untuk duplicate entry
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Anda sudah memiliki permohonan penutupan akun yang sedang diproses.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengajukan permohonan: ' . $e->getMessage()]);
    }
}
?>
