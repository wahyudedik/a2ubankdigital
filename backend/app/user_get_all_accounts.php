<?php
// File: app/user_get_all_accounts.php
// Penjelasan: Nasabah mengambil daftar semua rekening miliknya.

require_once 'auth_middleware.php';

try {
    $stmt = $pdo->prepare("SELECT id, account_number, account_type, balance, status FROM accounts WHERE user_id = ? ORDER BY account_type, created_at");
    $stmt->execute([$authenticated_user_id]);
    $all_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grouped_accounts = [
        'TABUNGAN' => [],
        'PINJAMAN' => [],
        'DEPOSITO' => []
    ];

    foreach ($all_accounts as $account) {
        $grouped_accounts[$account['account_type']][] = $account;
    }

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $grouped_accounts]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data rekening.']);
}
?>
