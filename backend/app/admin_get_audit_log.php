<?php
// File: app/admin_get_audit_log.php
// REVISI TOTAL: Memperbaiki bug SQL, mengambil transaction_code, dan menyempurnakan query.

require_once 'auth_middleware.php';

// Keamanan: Pastikan hanya Super Admin (1) dan Kepala Cabang (2) yang bisa mengakses.
if (!in_array($authenticated_user_role_id, [1, 2])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

// Paginasi & Filter
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 15;
$offset = ($page - 1) * $limit;
$actionFilter = $_GET['action'] ?? '';

try {
    // --- Membangun Query dengan Data Scoping ---
    $base_sql = "
        FROM audit_logs al
        JOIN users u ON al.user_id = u.id
        LEFT JOIN transactions t ON JSON_UNQUOTE(JSON_EXTRACT(al.details, '$.transaction_id')) = t.id
    ";
    $where_clauses = [];
    $params = [];

    // Terapkan filter unit/cabang jika yang login adalah Kepala Cabang
    if ($authenticated_user_role_id === 2 && !empty($accessible_unit_ids)) {
        $placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        $where_clauses[] = "u.unit_id IN ($placeholders)";
        $params = array_merge($params, $accessible_unit_ids);
    }

    if (!empty($actionFilter)) {
        $where_clauses[] = "al.action = ?";
        $params[] = $actionFilter;
    }

    $where_sql = empty($where_clauses) ? '' : 'WHERE ' . implode(' AND ', $where_clauses);

    // Hitung total untuk paginasi
    $stmt_count = $pdo->prepare("SELECT COUNT(al.id) " . $base_sql . $where_sql);
    $stmt_count->execute($params);
    $total_records = $stmt_count->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // PERBAIKAN: Menggunakan HANYA positional placeholders (?) untuk konsistensi
    $sql = "
        SELECT 
            al.id, u.full_name, al.action, al.details, al.ip_address, al.created_at,
            t.transaction_code
        " . $base_sql . $where_sql . "
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt_data = $pdo->prepare($sql);
    
    // PERBAIKAN: Binding parameter yang aman dan konsisten
    $param_index = 1;
    foreach ($params as $param_value) {
        $stmt_data->bindValue($param_index++, $param_value, PDO::PARAM_STR);
    }
    // Binding untuk LIMIT dan OFFSET
    $stmt_data->bindValue($param_index++, (int)$limit, PDO::PARAM_INT);
    $stmt_data->bindValue($param_index++, (int)$offset, PDO::PARAM_INT);
    
    $stmt_data->execute();
    $logs = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'pagination' => [
            'current_page' => (int)$page,
            'total_pages' => (int)$total_pages,
            'total_records' => (int)$total_records
        ],
        'data' => $logs
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil log audit: ' . $e->getMessage()]);
}

