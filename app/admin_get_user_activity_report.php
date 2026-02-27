<?php
// File: app/admin_get_user_activity_report.php
// Penjelasan: Laporan aktivitas pengguna untuk admin.
// REVISI: Menerapkan data scoping berdasarkan unit staf.

require_once 'auth_middleware.php';

$allowed_roles = [1, 2, 3]; // Kepala Unit ke atas
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    // --- Persiapan Data Scoping ---
    $unit_scope_sql_cp = "";
    $params_scope = [];
    if ($authenticated_user_role_id !== 1 && !empty($accessible_unit_ids)) {
        $placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        $unit_scope_sql_cp = " AND cp.unit_id IN ($placeholders)";
        $params_scope = $accessible_unit_ids;
    }

    // --- Kalkulasi dengan Scoping ---
    // Logins (tidak bisa di-scope dengan mudah tanpa mengubah tabel login_history)
    // Untuk saat ini kita biarkan global atau bisa dihilangkan jika perlu.
    $stmt_logins = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM login_history WHERE DATE(login_at) BETWEEN ? AND ?");
    $stmt_logins->execute([$start_date, $end_date]);
    $active_users = $stmt_logins->fetchColumn();

    // Transactions
    $sql_trx = "
        SELECT COUNT(t.id), SUM(t.amount) 
        FROM transactions t
        JOIN accounts a ON t.from_account_id = a.id OR t.to_account_id = a.id
        JOIN customer_profiles cp ON a.user_id = cp.user_id
        WHERE DATE(t.created_at) BETWEEN ? AND ? AND t.status = 'SUCCESS'" . $unit_scope_sql_cp;
    $params_trx = array_merge([$start_date, $end_date], $params_scope);
    $stmt_trx = $pdo->prepare($sql_trx);
    $stmt_trx->execute($params_trx);
    list($total_transactions, $total_volume) = $stmt_trx->fetch(PDO::FETCH_NUM);
    
    // New Users
    $sql_new = "
        SELECT COUNT(u.id) 
        FROM users u
        JOIN customer_profiles cp ON u.id = cp.user_id
        WHERE DATE(u.created_at) BETWEEN ? AND ? AND u.role_id = 9" . $unit_scope_sql_cp;
    $params_new = array_merge([$start_date, $end_date], $params_scope);
    $stmt_new = $pdo->prepare($sql_new);
    $stmt_new->execute($params_new);
    $new_users = $stmt_new->fetchColumn();

    $report = [
        'period_start' => $start_date,
        'period_end' => $end_date,
        'unique_active_users' => (int)$active_users,
        'total_transactions' => (int)$total_transactions,
        'total_transaction_volume' => (float)$total_volume,
        'new_customers' => (int)$new_users
    ];
    
    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $report]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil laporan aktivitas: ' . $e->getMessage()]);
}
