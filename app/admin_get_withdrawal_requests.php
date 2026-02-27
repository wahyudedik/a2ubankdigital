<?php
// File: app/admin_get_withdrawal_requests.php
// REVISI: Menerapkan data scoping berdasarkan unit/cabang staf.

require_once 'auth_middleware.php';

// Hanya Teller (5) ke atas yang bisa mengakses
if ($authenticated_user_role_id > 5) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$status = $_GET['status'] ?? 'PENDING';

try {
    // --- Membangun Query dengan Data Scoping ---
    $base_sql = "
        FROM withdrawal_requests wr
        JOIN users u ON wr.user_id = u.id
        JOIN customer_profiles cp ON u.id = cp.user_id
        JOIN withdrawal_accounts wa ON wr.withdrawal_account_id = wa.id
    ";
    $where_clauses = ["wr.status = ?"];
    $params = [$status];

    // Terapkan filter unit/cabang jika bukan Super Admin
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
    $order_sql = " ORDER BY wr.created_at ASC"; // Prioritaskan yang paling lama

    $final_sql = "
        SELECT 
            wr.id, wr.amount, wr.status, wr.created_at, wr.processed_at,
            u.full_name as customer_name,
            wa.bank_name, wa.account_number, wa.account_name
        " . $base_sql . $where_sql . $order_sql;
    
    $stmt = $pdo->prepare($final_sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $requests]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data permintaan penarikan: ' . $e->getMessage()]);
}
