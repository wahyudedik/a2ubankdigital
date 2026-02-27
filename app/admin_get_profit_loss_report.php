<?php
// File: app/admin_get_profit_loss_report.php
// Penjelasan: REVISI TOTAL - Mengkalkulasi laba rugi secara akurat dengan
// menyertakan pendapatan bunga dari pinjaman dan pendapatan biaya dari transaksi.

require_once 'auth_middleware.php';

// Hanya peran manajerial yang bisa mengakses
$allowed_roles = [1, 2, 3];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

try {
    // --- Persiapan Data Scoping ---
    $unit_scope_sql_transactions = "";
    $unit_scope_sql_installments = "";
    $params_scope = [];

    if ($authenticated_user_role_id !== 1 && !empty($accessible_unit_ids)) {
        $placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        
        // Klausa join dan where untuk tabel 'transactions'
        $unit_scope_sql_transactions = "
            LEFT JOIN accounts a ON t.from_account_id = a.id OR t.to_account_id = a.id
            LEFT JOIN customer_profiles cp ON a.user_id = cp.user_id
            WHERE cp.unit_id IN ($placeholders) AND
        ";

        // Klausa join dan where untuk tabel 'loan_installments'
        $unit_scope_sql_installments = "
            JOIN loans l ON li.loan_id = l.id
            JOIN customer_profiles cp ON l.user_id = cp.user_id
            WHERE cp.unit_id IN ($placeholders) AND
        ";
        
        $params_scope = $accessible_unit_ids;
    }

    // --- 1. Kalkulasi Pendapatan dari Biaya (Transaction Fees) ---
    $sql_fee_revenue = "
        SELECT COALESCE(SUM(t.fee), 0)
        FROM transactions t
        " . ($unit_scope_sql_transactions ?: "WHERE") . "
             t.status = 'SUCCESS'
             AND DATE(t.created_at) BETWEEN ? AND ?
    ";
    $params_fee = array_merge($params_scope, [$start_date, $end_date]);
    $stmt_fee = $pdo->prepare($sql_fee_revenue);
    $stmt_fee->execute($params_fee);
    $revenue_from_fees = (float)$stmt_fee->fetchColumn();

    // --- 2. Kalkulasi Pendapatan dari Bunga Pinjaman (Loan Interest) ---
    $sql_interest_revenue = "
        SELECT COALESCE(SUM(li.interest_amount), 0)
        FROM loan_installments li
        " . ($unit_scope_sql_installments ?: "WHERE") . "
             li.status = 'PAID'
             AND DATE(li.payment_date) BETWEEN ? AND ?
    ";
    $params_interest = array_merge($params_scope, [$start_date, $end_date]);
    $stmt_interest = $pdo->prepare($sql_interest_revenue);
    $stmt_interest->execute($params_interest);
    $revenue_from_interest = (float)$stmt_interest->fetchColumn();

    // --- 3. Kalkulasi Beban Bunga Tabungan (Savings Interest Expense) ---
    $sql_expense = "
        SELECT COALESCE(SUM(t.amount), 0)
        FROM transactions t
        " . ($unit_scope_sql_transactions ?: "WHERE") . "
             t.transaction_type = 'BUNGA_TABUNGAN'
             AND t.status = 'SUCCESS'
             AND DATE(t.created_at) BETWEEN ? AND ?
    ";
    $params_expense = array_merge($params_scope, [$start_date, $end_date]);
    $stmt_expense = $pdo->prepare($sql_expense);
    $stmt_expense->execute($params_expense);
    $total_expense = (float)$stmt_expense->fetchColumn();

    // --- 4. Kalkulasi Final ---
    $total_revenue = $revenue_from_fees + $revenue_from_interest;
    $net_profit = $total_revenue - $total_expense;

    $report_data = [
        'revenue_from_fees' => $revenue_from_fees,
        'revenue_from_interest' => $revenue_from_interest,
        'total_revenue' => $total_revenue,
        'total_expense' => $total_expense,
        'net_profit' => $net_profit,
        'period_start' => $start_date,
        'period_end' => $end_date
    ];

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $report_data]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil laporan laba rugi: ' . $e->getMessage()]);
}
?>