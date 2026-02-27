<?php
// File: app/admin_get_marketing_report.php
// Penjelasan: Laporan akuisisi nasabah baru oleh staf marketing.
// REVISI: Mengubah `cp.created_at` menjadi `u.created_at` untuk filter tanggal yang benar.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: Kepala Cabang, Kepala Unit, Super Admin
$allowed_roles = [1, 2, 3];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    // REVISI: Mengubah `cp.created_at` menjadi `u.created_at`
    $base_sql = "
        FROM customer_profiles cp
        JOIN users u_staff ON cp.registered_by = u_staff.id
        JOIN users u ON cp.user_id = u.id 
        WHERE cp.registration_method = 'OFFLINE' 
          AND DATE(u.created_at) BETWEEN ? AND ?
    ";
    $params = [$start_date, $end_date];

    // Logika Data Scoping
    if ($authenticated_user_role_id !== 1 && !empty($accessible_unit_ids)) {
        $placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        $base_sql .= " AND cp.unit_id IN ($placeholders)";
        $params = array_merge($params, $accessible_unit_ids);
    }
    
    $final_sql = "
        SELECT 
            u_staff.full_name as marketing_name,
            COUNT(cp.user_id) as new_customers
        " . $base_sql . "
        GROUP BY u_staff.id, u_staff.full_name
        ORDER BY new_customers DESC
    ";
    
    $stmt = $pdo->prepare($final_sql);
    $stmt->execute($params);
    $report = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $report]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil laporan marketing: ' . $e->getMessage()]);
}
