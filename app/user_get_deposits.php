<?php
// File: app/user_get_deposits.php
// Penjelasan: Nasabah mengambil daftar rekening deposito miliknya.

require_once 'auth_middleware.php';

try {
    $stmt = $pdo->prepare("
        SELECT 
            a.id, a.account_number, a.balance, a.status, a.maturity_date,
            dp.product_name
        FROM accounts a
        JOIN deposit_products dp ON a.deposit_product_id = dp.id
        WHERE a.user_id = ? AND a.account_type = 'DEPOSITO'
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$authenticated_user_id]);
    $deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $deposits]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data deposito.']);
}
