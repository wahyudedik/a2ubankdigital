<?php
// File: app/user_get_withdrawal_accounts.php
// Penjelasan: Nasabah mengambil daftar rekening penarikan miliknya.

require_once 'auth_middleware.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM withdrawal_accounts WHERE user_id = ? ORDER BY bank_name ASC");
    $stmt->execute([$authenticated_user_id]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $accounts]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data rekening.']);
}
