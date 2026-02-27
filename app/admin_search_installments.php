<?php
// File: app/admin_search_installments.php
// Penjelasan: REVISI - Menambahkan logika data scoping untuk memastikan staf
// hanya bisa mencari angsuran dari nasabah di dalam unit kerjanya.

require_once 'auth_middleware.php';

// Hanya peran yang relevan yang bisa mengakses (Teller ke atas)
if ($authenticated_user_role_id > 6) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$search_term = $_GET['q'] ?? '';

if (strlen($search_term) < 3) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Kata kunci pencarian minimal 3 karakter.']);
    exit();
}

try {
    $search_param = "%{$search_term}%";
    
    // --- Membangun Query dengan Data Scoping ---
    $base_sql = "
        FROM loan_installments li
        JOIN loans l ON li.loan_id = l.id
        JOIN users u ON l.user_id = u.id
        JOIN customer_profiles cp ON u.id = cp.user_id
        JOIN loan_products lp ON l.loan_product_id = lp.id
    ";
    
    $where_clauses = [
        "(u.full_name LIKE ? OR l.id LIKE ?)",
        "li.status IN ('PENDING', 'OVERDUE')"
    ];
    $params = [$search_param, $search_param];

    // Terapkan filter unit/cabang jika bukan Super Admin
    if ($authenticated_user_role_id !== 1) {
        if (empty($accessible_unit_ids)) {
            // Jika staf tidak punya akses ke unit manapun, jangan tampilkan data
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
            li.id as installment_id,
            li.installment_number,
            li.amount_due,
            li.penalty_amount,
            li.due_date,
            l.id as loan_id,
            lp.product_name,
            u.full_name as customer_name
    " . $base_sql . $where_sql . "
        ORDER BY u.full_name, l.id, li.due_date ASC
        LIMIT 10
    ";

    $stmt = $pdo->prepare($final_sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $results]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mencari angsuran: ' . $e->getMessage()]);
}
