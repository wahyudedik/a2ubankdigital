<?php
// File: app/admin_get_product_performance_report.php
// Penjelasan: Endpoint baru untuk laporan performa produk pinjaman dan deposito.

require_once 'auth_middleware.php';

// Hanya peran manajerial yang bisa mengakses
$allowed_roles = [1, 2, 3];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

try {
    // --- Persiapan Data Scoping ---
    $unit_scope_sql_loan = "";
    $unit_scope_sql_deposit = "";
    $params = [];

    if ($authenticated_user_role_id !== 1 && !empty($accessible_unit_ids)) {
        $placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        // Scope untuk pinjaman (di-join melalui users)
        $unit_scope_sql_loan = " JOIN users u ON l.user_id = u.id JOIN customer_profiles cp ON u.id = cp.user_id WHERE cp.unit_id IN ($placeholders)";
        // Scope untuk deposito (di-join melalui users)
        $unit_scope_sql_deposit = " JOIN users u ON a.user_id = u.id JOIN customer_profiles cp ON u.id = cp.user_id WHERE cp.unit_id IN ($placeholders)";
        $params = $accessible_unit_ids;
    }

    // --- Laporan Performa Pinjaman ---
    $sql_loans = "
        SELECT 
            lp.product_name,
            COUNT(l.id) as total_disbursed,
            SUM(l.loan_amount) as total_amount
        FROM loans l
        JOIN loan_products lp ON l.loan_product_id = lp.id
        " . ($unit_scope_sql_loan ?: "WHERE 1=1") . "
        AND l.status = 'DISBURSED'
        GROUP BY lp.product_name
        ORDER BY total_amount DESC
    ";
    $stmt_loans = $pdo->prepare($sql_loans);
    $stmt_loans->execute($params);
    $loan_performance = $stmt_loans->fetchAll(PDO::FETCH_ASSOC);

    // --- Laporan Performa Deposito ---
     $sql_deposits = "
        SELECT 
            dp.product_name,
            COUNT(a.id) as total_accounts,
            SUM(a.balance) as total_balance
        FROM accounts a
        JOIN deposit_products dp ON a.deposit_product_id = dp.id
        " . ($unit_scope_sql_deposit ?: "WHERE 1=1") . "
        AND a.account_type = 'DEPOSITO' AND a.status = 'ACTIVE'
        GROUP BY dp.product_name
        ORDER BY total_balance DESC
    ";
    $stmt_deposits = $pdo->prepare($sql_deposits);
    $stmt_deposits->execute($params);
    $deposit_performance = $stmt_deposits->fetchAll(PDO::FETCH_ASSOC);

    $response_data = [
        'loans' => $loan_performance,
        'deposits' => $deposit_performance
    ];

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $response_data]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil laporan performa produk: ' . $e->getMessage()]);
}

