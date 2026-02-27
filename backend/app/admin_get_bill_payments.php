<?php
// File: app/admin_get_bill_payments.php
// Penjelasan: Endpoint baru untuk admin mengambil riwayat transaksi pembayaran tagihan.

require_once 'auth_middleware.php';

// Hanya peran staf yang bisa mengakses
if ($authenticated_user_role_id > 8) { // Semua staf bisa, kecuali nasabah
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

// Parameter untuk Paginasi, Pencarian, dan Filter
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$offset = ($page - 1) * $limit;

try {
    // --- Membangun Query dengan Data Scoping ---
    $base_sql = "FROM transactions t 
                 JOIN accounts a ON t.from_account_id = a.id
                 JOIN users u ON a.user_id = u.id
                 JOIN customer_profiles cp ON u.id = cp.user_id";
    $where_clauses = ["t.transaction_type = 'PEMBAYARAN_TAGIHAN'"];
    $params = [];

    // Terapkan data scoping jika bukan Super Admin
    if ($authenticated_user_role_id !== 1 && !empty($accessible_unit_ids)) {
        $placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        $where_clauses[] = "cp.unit_id IN ($placeholders)";
        $params = array_merge($params, $accessible_unit_ids);
    }

    if (!empty($search)) {
        $where_clauses[] = "(u.full_name LIKE ? OR t.transaction_code LIKE ? OR t.description LIKE ?)";
        $search_param = "%" . $search . "%";
        array_push($params, $search_param, $search_param, $search_param);
    }

    if (!empty($status)) {
        $where_clauses[] = "t.status = ?";
        $params[] = $status;
    }

    $where_sql = " WHERE " . implode(" AND ", $where_clauses);

    // --- Query Total untuk Paginasi ---
    $sql_total = "SELECT COUNT(t.id) " . $base_sql . $where_sql;
    $stmt_total = $pdo->prepare($sql_total);
    $stmt_total->execute($params);
    $total_records = $stmt_total->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // --- Query Data Utama ---
    $sql_data = "SELECT 
                    t.id, t.transaction_code, t.amount, t.fee, t.description, t.status, t.created_at,
                    u.full_name as customer_name
                " . $base_sql . $where_sql . " ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
    
    $params_for_data = $params;
    $params_for_data[] = $limit;
    $params_for_data[] = $offset;

    $stmt_data = $pdo->prepare($sql_data);
    foreach ($params_for_data as $key => $value) {
        $stmt_data->bindValue($key + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    
    $stmt_data->execute();
    $transactions = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'data' => $transactions,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => (int)$total_pages,
            'total_records' => (int)$total_records
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil riwayat pembayaran tagihan: ' . $e->getMessage()]);
}
