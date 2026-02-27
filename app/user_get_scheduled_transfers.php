<?php
// File: app/user_get_scheduled_transfers.php
// Penjelasan: Nasabah melihat daftar transfer terjadwal miliknya.

require_once 'auth_middleware.php';

try {
    $stmt = $pdo->prepare("SELECT id, to_account_number, amount, description, scheduled_date, status, failure_reason FROM scheduled_transfers WHERE user_id = ? ORDER BY scheduled_date DESC");
    $stmt->execute([$authenticated_user_id]);
    $scheduled_transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $scheduled_transfers]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data transfer terjadwal.']);
}
?>
