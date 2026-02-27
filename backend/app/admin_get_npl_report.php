<?php
// File: app/admin_get_npl_report.php
// Penjelasan: Laporan Non-Performing Loan (Pinjaman Macet).
// REVISI: Menerapkan data scoping berdasarkan unit staf.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: Kepala Unit, Kepala Cabang, Super Admin, Debt Collector
$allowed_roles = [1, 2, 3, 8]; // Dulu '7' untuk Analis, sekarang 8 untuk Debt Collector
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$days_overdue = $_GET['days_overdue'] ?? 30;

try {
    // --- Membangun Query ---
    $base_sql = "
        FROM loan_installments li
        JOIN loans l ON li.loan_id = l.id
        JOIN users u ON l.user_id = u.id
        JOIN customer_profiles cp ON u.id = cp.user_id
    ";
    $where_clauses = ["li.status = 'PENDING'", "li.due_date < CURDATE()", "DATEDIFF(CURDATE(), li.due_date) >= ?"];
    $params = [$days_overdue];

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
            u.full_name, u.phone_number, l.loan_amount,
            li.due_date, li.amount_due as installment_amount,
            DATEDIFF(CURDATE(), li.due_date) as overdue_days
    " . $base_sql . $where_sql . " ORDER BY overdue_days DESC";
    
    $stmt = $pdo->prepare($final_sql);
    $stmt->execute($params);
    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $report_data]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil laporan NPL: ' . $e->getMessage()]);
}
