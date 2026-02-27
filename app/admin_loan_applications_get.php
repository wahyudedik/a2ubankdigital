<?php
// File: app/admin_loan_applications_get.php
// Penjelasan: Mengambil daftar semua pengajuan pinjaman dengan paginasi dan filter status.
// REVISI: Menggunakan kolom tenor dan tenor_unit yang baru.

require_once 'auth_middleware.php';

if ($authenticated_user_role_id == 9) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

// --- Parameter ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

try {
    // --- Membangun Query ---
    $base_sql = "FROM loans l
                 JOIN users u ON l.user_id = u.id
                 JOIN customer_profiles cp ON u.id = cp.user_id
                 JOIN loan_products lp ON l.loan_product_id = lp.id";
    $where_clauses = [];
    $params = [];

    if (!empty($status)) {
        $where_clauses[] = "l.status = ?";
        $params[] = $status;
    }

    // --- LOGIKA DATA SCOPING BARU ---
    if ($authenticated_user_role_id !== 1) {
        if (empty($accessible_unit_ids)) {
            echo json_encode(['status' => 'success', 'data' => [], 'pagination' => ['current_page' => 1, 'total_pages' => 0, 'total_records' => 0]]);
            exit();
        }
        
        $placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        $where_clauses[] = "cp.unit_id IN ($placeholders)";
        $params = array_merge($params, $accessible_unit_ids);
    }
    
    $where_sql = !empty($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";
    
    // --- Query Total ---
    $sql_total = "SELECT COUNT(l.id) " . $base_sql . $where_sql;
    $stmt_total = $pdo->prepare($sql_total);
    $stmt_total->execute($params);
    $total_records = $stmt_total->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // --- Query Data ---
    // REVISI: Mengubah l.tenor_months menjadi l.tenor dan menambahkan l.tenor_unit
    $sql_data = "SELECT 
                    l.id, l.status, l.loan_amount, l.tenor, l.tenor_unit, l.application_date,
                    u.full_name as customer_name,
                    lp.product_name
                 " . $base_sql . $where_sql . " ORDER BY l.application_date DESC LIMIT ? OFFSET ?";
    
    $params_for_data = $params;
    $params_for_data[] = $limit;
    $params_for_data[] = $offset;

    $stmt_data = $pdo->prepare($sql_data);
    
    foreach ($params_for_data as $key => $value) {
        $param_type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt_data->bindValue($key + 1, $value, $param_type);
    }
    
    $stmt_data->execute();
    $applications = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'data' => $applications,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => (int)$total_pages,
            'total_records' => (int)$total_records
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    // Menambahkan detail error SQL ke log untuk debugging
    error_log("Admin Get Loan Applications Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data pengajuan pinjaman: ' . $e->getMessage()]);
}
