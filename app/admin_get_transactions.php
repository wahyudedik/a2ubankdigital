<?php
// File: app/admin_get_transactions.php
// Penjelasan: PERBAIKAN FINAL V6 - Menambahkan logika CASE untuk mengisi
// transaction_type yang kosong pada data lama, berdasarkan konteks transaksi.

require_once 'auth_middleware.php';

if ($authenticated_user_role_id > 8) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

// --- Parameter ---
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 15;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$offset = ($page - 1) * $limit;

try {
    // --- Membangun Query ---
    $base_select = "
        SELECT
            t.id,
            t.transaction_code,
            CASE
                WHEN t.transaction_type IS NULL OR t.transaction_type = ''
                THEN 
                    CASE
                        WHEN t.processed_by IS NOT NULL AND li.id IS NOT NULL THEN 'BAYAR_CICILAN_TUNAI'
                        WHEN t.processed_by IS NOT NULL AND t.to_account_id IS NOT NULL THEN 'SETOR_TUNAI'
                        WHEN t.processed_by IS NOT NULL AND t.from_account_id IS NOT NULL THEN 'TARIK_TUNAI'
                        ELSE 'TIDAK DIKETAHUI'
                    END
                ELSE t.transaction_type
            END as transaction_type,
            t.amount,
            t.status,
            t.created_at,
            COALESCE(loan_owner_user.full_name, from_user.full_name, 'Sistem/Eksternal') as from_name,
            CASE
                WHEN t.processed_by IS NOT NULL 
                THEN CONCAT('Teller: ', COALESCE(staff_user.full_name, CONCAT('ID#', t.processed_by)))
                WHEN t.transaction_type LIKE 'BAYAR_CICILAN%' THEN 'A2U Bank Digital (Pembayaran)'
                ELSE to_user.full_name
            END as to_name
    ";

    $base_from = "
        FROM transactions t
        LEFT JOIN accounts from_acc ON t.from_account_id = from_acc.id
        LEFT JOIN users from_user ON from_acc.user_id = from_user.id
        LEFT JOIN customer_profiles from_cp ON from_user.id = from_cp.user_id
        LEFT JOIN accounts to_acc ON t.to_account_id = to_acc.id
        LEFT JOIN users to_user ON to_acc.user_id = to_user.id
        LEFT JOIN customer_profiles to_cp ON to_user.id = to_cp.user_id
        LEFT JOIN loan_installments li ON t.id = li.transaction_id
        LEFT JOIN loans l ON li.loan_id = l.id
        LEFT JOIN users loan_owner_user ON l.user_id = loan_owner_user.id
        LEFT JOIN customer_profiles loan_owner_cp ON loan_owner_user.id = loan_owner_cp.user_id
        LEFT JOIN users staff_user ON t.processed_by = staff_user.id
    ";

    $where_clauses = ["1=1"];
    $params = [];

    // --- Data Scoping ---
    if ($authenticated_user_role_id !== 1 && !empty($accessible_unit_ids)) {
        $placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        $where_clauses[] = "(from_cp.unit_id IN ($placeholders) OR to_cp.unit_id IN ($placeholders) OR loan_owner_cp.unit_id IN ($placeholders))";
        $params = array_merge($params, $accessible_unit_ids, $accessible_unit_ids, $accessible_unit_ids);
    }

    // --- Filter Tambahan ---
    if (!empty($search)) {
        $search_param = "%{$search}%";
        $where_clauses[] = "(t.transaction_code LIKE ? OR from_user.full_name LIKE ? OR to_user.full_name LIKE ? OR loan_owner_user.full_name LIKE ? OR t.description LIKE ?)";
        array_push($params, $search_param, $search_param, $search_param, $search_param, $search_param);
    }

    if (!empty($type)) {
        $where_clauses[] = "t.transaction_type = ?";
        $params[] = $type;
    }

    $where_sql = " WHERE " . implode(" AND ", $where_clauses);

    // --- Query Total untuk Paginasi ---
    $sql_total = "SELECT COUNT(DISTINCT t.id) " . $base_from . $where_sql;
    $stmt_total = $pdo->prepare($sql_total);
    $stmt_total->execute($params);
    $total_records = $stmt_total->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // --- Query Data Utama ---
    $sql_data = $base_select . $base_from . $where_sql . "
        GROUP BY t.id
        ORDER BY t.created_at DESC
        LIMIT ? OFFSET ?
    ";

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
            'total_pages' => (int) $total_pages,
            'total_records' => (int) $total_records
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Admin Get Transactions Error: " . $e->getMessage() . " | SQL: " . ($sql_data ?? 'N/A'));
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data transaksi: ' . $e->getMessage()]);
}

