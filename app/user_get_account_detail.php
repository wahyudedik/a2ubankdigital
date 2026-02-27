<?php
// File: app/user_get_account_detail.php
// Penjelasan: Mengambil detail satu rekening spesifik milik pengguna yang login.

require_once 'auth_middleware.php';

$account_id = $_GET['id'] ?? null;
if (!$account_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Rekening wajib diisi.']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT id, account_number, account_type, balance, status, created_at
        FROM accounts
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$account_id, $authenticated_user_id]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($account) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'data' => $account]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Rekening tidak ditemukan atau Anda tidak memiliki akses.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
}
?>
