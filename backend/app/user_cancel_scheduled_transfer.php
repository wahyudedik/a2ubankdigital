<?php
// File: app/user_cancel_scheduled_transfer.php
// Penjelasan: Nasabah membatalkan transfer yang masih tertunda.

require_once 'auth_middleware.php';

$input = json_decode(file_get_contents('php://input'), true);
$transfer_id = $input['transfer_id'] ?? null;

if (!$transfer_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Transfer wajib diisi.']);
    exit();
}

try {
    // Hapus hanya jika statusnya PENDING dan milik user yang sedang login
    $stmt = $pdo->prepare("DELETE FROM scheduled_transfers WHERE id = ? AND user_id = ? AND status = 'PENDING'");
    $stmt->execute([$transfer_id, $authenticated_user_id]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Transfer terjadwal berhasil dibatalkan.']);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Transfer tidak ditemukan atau tidak dapat dibatalkan.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal membatalkan transfer.']);
}
?>
