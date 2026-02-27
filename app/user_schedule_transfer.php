<?php
// File: app/user_schedule_transfer.php
// Penjelasan: Nasabah membuat transfer terjadwal.

require_once 'auth_middleware.php';

/*
    CATATAN DATABASE: Memerlukan tabel `scheduled_transfers`.
    CREATE TABLE `scheduled_transfers` (
      `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id` int(10) UNSIGNED NOT NULL,
      `from_account_id` int(10) UNSIGNED NOT NULL,
      `to_account_number` varchar(20) NOT NULL,
      `amount` decimal(20,2) NOT NULL,
      `description` text,
      `scheduled_date` date NOT NULL,
      `status` enum('PENDING','EXECUTED','FAILED') NOT NULL DEFAULT 'PENDING',
      `executed_at` timestamp NULL,
      `failure_reason` text,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB;
*/

$input = json_decode(file_get_contents('php://input'), true);
$required = ['to_account_number', 'amount', 'scheduled_date'];
// Validasi input
// ... (serupa dengan file lain)

try {
    // Ambil rekening tabungan user
    $stmt_acc = $pdo->prepare("SELECT id FROM accounts WHERE user_id = ? AND account_type = 'TABUNGAN' AND status = 'ACTIVE'");
    $stmt_acc->execute([$authenticated_user_id]);
    $from_account_id = $stmt_acc->fetchColumn();

    if (!$from_account_id) throw new Exception("Rekening tabungan tidak ditemukan.");

    // Cek tanggal, harus di masa depan
    if (strtotime($input['scheduled_date']) <= time()) {
        throw new Exception("Tanggal penjadwalan harus di masa depan.");
    }
    
    $stmt_insert = $pdo->prepare("
        INSERT INTO scheduled_transfers (user_id, from_account_id, to_account_number, amount, description, scheduled_date)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt_insert->execute([$authenticated_user_id, $from_account_id, $input['to_account_number'], $input['amount'], $input['description'] ?? '', $input['scheduled_date']]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Transfer berhasil dijadwalkan.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menjadwalkan transfer: ' . $e->getMessage()]);
}
?>
