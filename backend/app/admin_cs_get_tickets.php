<?php
// File: app/admin_cs_get_tickets.php
// Penjelasan: CS melihat daftar tiket keluhan.
// REVISI: Memperbaiki query untuk data scoping yang benar dan nama kolom.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: CS, Kepala Unit ke atas
$allowed_roles = [1, 2, 3, 6];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$status = $_GET['status'] ?? 'OPEN'; // Filter berdasarkan status
$params = [$status];

try {
    // --- Membangun Query dengan Data Scoping ---
    $base_sql = "
        FROM support_tickets st
        JOIN users u_cust ON st.customer_user_id = u_cust.id
        LEFT JOIN users u_staff ON st.created_by_staff_id = u_staff.id
        LEFT JOIN customer_profiles cp ON u_cust.id = cp.user_id
    ";
    $where_clauses = ["st.status = ?"];

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
    $final_sql = "
        SELECT 
            st.id, 
            u_cust.full_name as customer_name, 
            u_staff.full_name as created_by, 
            st.subject, 
            st.status, 
            st.created_at
    " . $base_sql . $where_sql . " ORDER BY st.created_at DESC";
    
    $stmt = $pdo->prepare($final_sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $tickets]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data tiket: ' . $e->getMessage()]);
}
