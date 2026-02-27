<?php
// File: app/admin_get_customer_growth_report.php
// Penjelasan: REVISI - Memperbaiki logika untuk selalu mengembalikan data 30 hari penuh,
// termasuk hari-hari dengan 0 pendaftar baru, agar grafik bisa ditampilkan dengan benar.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: Marketing, Kepala Unit ke atas
$allowed_roles = [1, 2, 3, 4];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-29 days')); // 30 hari terakhir
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    // --- Membangun Query ---
    $base_sql = "
        FROM users u
        JOIN customer_profiles cp ON u.id = cp.user_id
    ";
    $where_clauses = ["u.role_id = 9", "DATE(u.created_at) BETWEEN ? AND ?"];
    $params = [$start_date, $end_date];

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
        SELECT DATE(u.created_at) as registration_date, COUNT(u.id) as new_customers
    " . $base_sql . $where_sql . "
        GROUP BY DATE(u.created_at)
        ORDER BY registration_date ASC
    ";
    
    $stmt = $pdo->prepare($final_sql);
    $stmt->execute($params);
    $report_data_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // --- LOGIKA BARU: Pastikan data 30 hari penuh ---
    $report_data_complete = [];
    $current_date = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);

    while ($current_date <= $end_date_obj) {
        $date_key = $current_date->format('Y-m-d');
        $report_data_complete[] = [
            'registration_date' => $date_key,
            'new_customers' => (int)($report_data_raw[$date_key] ?? 0)
        ];
        $current_date->modify('+1 day');
    }
    // --- AKHIR LOGIKA BARU ---

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $report_data_complete]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil laporan pertumbuhan: ' . $e->getMessage()]);
}
