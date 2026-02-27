<?php
// File: app/admin_get_account_balance_report.php
// Penjelasan: Laporan total saldo per jenis akun.
// REVISI: Menerapkan data scoping berdasarkan unit staf.

require_once 'auth_middleware.php';

$allowed_roles = [1, 2, 3]; // Kepala Unit ke atas
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

try {
    // --- Membangun Query ---
    $base_sql = "
        FROM accounts a
        JOIN customer_profiles cp ON a.user_id = cp.user_id
    ";
    $where_clauses = ["a.status = 'ACTIVE'"];
    $params = [];

    // --- LOGIKA DATA SCOPING ---
    if ($authenticated_user_role_id !== 1) {
        if (empty($accessible_unit_ids)) {
            echo json_encode(['status' => 'success', 'data' => []]);
            exit();
        }
        $placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        $where_clauses[] = "cp.unit_id IN ($placeholders)";
        $params = array_merge($params, $accessible_unit_ids);
    }
    
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
    
    $final_sql = "
        SELECT 
            a.account_type,
            COUNT(a.id) as number_of_accounts,
            SUM(a.balance) as total_balance
    " . $base_sql . $where_sql . " GROUP BY a.account_type";
    
    $stmt = $pdo->prepare($final_sql);
    $stmt->execute($params);
    $report = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $report]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil laporan saldo: ' . $e->getMessage()]);
}
