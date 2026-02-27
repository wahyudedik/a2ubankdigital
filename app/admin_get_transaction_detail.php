<?php
// File: app/admin_get_transaction_detail.php
// Penjelasan: Endpoint baru untuk mengambil detail lengkap satu transaksi spesifik
// oleh admin, dengan menerapkan data scoping.

require_once 'auth_middleware.php';

$transaction_id = $_GET['id'] ?? null;
if (!$transaction_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Transaksi wajib diisi.']);
    exit();
}

try {
    $base_sql = "
        FROM transactions t
        LEFT JOIN accounts from_acc ON t.from_account_id = from_acc.id
        LEFT JOIN users from_user ON from_acc.user_id = from_user.id
        LEFT JOIN customer_profiles from_cp ON from_user.id = from_cp.user_id
        LEFT JOIN accounts to_acc ON t.to_account_id = to_acc.id
        LEFT JOIN users to_user ON to_acc.user_id = to_user.id
        LEFT JOIN customer_profiles to_cp ON to_user.id = to_cp.user_id
    ";
    $where_clauses = ["t.id = ?"];
    $params = [$transaction_id];

    // Terapkan data scoping jika bukan Super Admin
    if ($authenticated_user_role_id !== 1 && !empty($accessible_unit_ids)) {
        $placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        $where_clauses[] = "(from_cp.unit_id IN ($placeholders) OR to_cp.unit_id IN ($placeholders))";
        $params = array_merge($params, $accessible_unit_ids, $accessible_unit_ids);
    }
    
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);

    $sql = "
        SELECT
            t.*,
            from_acc.account_number as from_account_number,
            from_user.full_name as from_user_name,
            to_acc.account_number as to_account_number,
            to_user.full_name as to_user_name
    " . $base_sql . $where_sql;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($transaction) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'data' => $transaction]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Transaksi tidak ditemukan atau Anda tidak memiliki akses.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil detail transaksi: ' . $e->getMessage()]);
}
