<?php
// File: app/admin_get_topup_requests.php
// Penjelasan: Admin mengambil daftar permintaan isi saldo.
// REVISI: Menerapkan data scoping dan memperbaiki urutan data.

require_once 'auth_middleware.php';

// Teller ke atas bisa mengakses
if ($authenticated_user_role_id > 6) { // Teller (5), CS (6)
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$status = $_GET['status'] ?? 'PENDING';

try {
    // --- Membangun Query ---
    $base_sql = "
        FROM topup_requests tr
        JOIN users u ON tr.user_id = u.id
        JOIN customer_profiles cp ON u.id = cp.user_id
    ";
    $where_clauses = ["tr.status = ?"];
    $params = [$status];

    // --- LOGIKA DATA SCOPING ---
    // Terapkan filter unit/cabang jika yang login BUKAN Super Admin.
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
    
    // Urutkan dari yang paling lama untuk antrian PENDING
    $order_sql = " ORDER BY " . ($status === 'PENDING' ? "tr.created_at ASC" : "tr.processed_at DESC");

    $final_sql = "
        SELECT tr.id, tr.amount, tr.payment_method, tr.proof_of_payment_url, tr.status, tr.created_at, tr.processed_at, u.full_name as customer_name
        " . $base_sql . $where_sql . $order_sql;

    $stmt = $pdo->prepare($final_sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $requests]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data permintaan: ' . $e->getMessage()]);
}
