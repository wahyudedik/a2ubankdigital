<?php
// File: app/user_get_deposit_detail.php
// Penjelasan: Nasabah melihat detail rekening deposito miliknya, termasuk bunga berjalan.

require_once 'auth_middleware.php';

$deposit_id = $_GET['id'] ?? 0;
if ($deposit_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Deposito tidak valid.']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            a.id, a.account_number, a.balance as principal, a.status, a.created_at as placement_date, a.maturity_date,
            dp.product_name, dp.interest_rate_pa, dp.tenor_months
        FROM accounts a
        JOIN deposit_products dp ON a.deposit_product_id = dp.id
        WHERE a.id = ? AND a.user_id = ? AND a.account_type = 'DEPOSITO'
    ");
    $stmt->execute([$deposit_id, $authenticated_user_id]);
    $deposit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$deposit) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Rekening deposito tidak ditemukan.']);
        exit();
    }

    // Hitung perkiraan bunga berjalan (simple interest)
    $principal = (float)$deposit['principal'];
    $rate_pa = (float)$deposit['interest_rate_pa'];
    $placement_date = new DateTime($deposit['placement_date']);
    $today = new DateTime();
    $days_passed = $today->diff($placement_date)->days;
    // Bunga dihitung sampai hari ini atau sampai jatuh tempo, mana yang lebih dulu
    $maturity_datetime = new DateTime($deposit['maturity_date']);
    if ($today > $maturity_datetime) {
        $days_passed = $maturity_datetime->diff($placement_date)->days;
    }
    
    $interest_earned = ($principal * ($rate_pa / 100) * $days_passed) / 365;
    
    $deposit['interest_earned'] = round($interest_earned, 2);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $deposit]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil detail deposito: ' . $e->getMessage()]);
}
