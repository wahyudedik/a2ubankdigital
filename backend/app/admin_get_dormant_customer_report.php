<?php
// File: app/admin_get_dormant_customer_report.php
// Penjelasan: Admin melihat daftar nasabah dengan akun dormant.
// REVISI: Menerapkan data scoping berdasarkan unit staf.

require_once 'auth_middleware.php';

if ($authenticated_user_role_id > 3) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

try {
    $base_sql = "
        FROM users u
        JOIN accounts a ON u.id = a.user_id
        JOIN customer_profiles cp ON u.id = cp.user_id
        WHERE a.status = 'DORMANT'
    ";
    $params = [];

    // LOGIKA BARU: Terapkan Data Scoping
    if ($authenticated_user_role_id !== 1 && !empty($accessible_unit_ids)) {
        $placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        $base_sql .= " AND cp.unit_id IN ($placeholders)";
        $params = array_merge($params, $accessible_unit_ids);
    }
    
    $final_sql = "
        SELECT u.id, u.full_name, u.email, u.phone_number, a.account_number, a.balance, a.updated_at as last_activity
    " . $base_sql . " ORDER BY a.updated_at ASC";
    
    $stmt = $pdo->prepare($final_sql);
    $stmt->execute($params);
    $report = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $report]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil laporan: ' . $e->getMessage()]);
}
