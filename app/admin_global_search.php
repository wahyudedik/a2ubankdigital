<?php
// File: app/admin_global_search.php
// Penjelasan: Admin mencari nasabah berdasarkan nama, email, telepon, atau no. rekening.
// REVISI: Menambahkan data scoping berdasarkan unit.

require_once 'auth_middleware.php';

$allowed_roles = [1, 2, 3, 6];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$query = $_GET['q'] ?? '';
if (strlen($query) < 3) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Kata kunci pencarian minimal 3 karakter.']);
    exit();
}

try {
    $search_term = '%' . $query . '%';
    $in_placeholders = implode(',', array_fill(0, count($unit_scope), '?'));

    // REVISI: Menambahkan `AND u.unit_id IN (...)` ke dalam klausa WHERE
    $sql = "
        SELECT DISTINCT u.id, u.full_name, u.email, u.phone_number, u.bank_id, a.account_number
        FROM users u
        LEFT JOIN accounts a ON u.id = a.user_id
        WHERE u.role_id = 9 
          AND u.unit_id IN ($in_placeholders)
          AND (
            u.full_name LIKE ? OR
            u.email LIKE ? OR
            u.phone_number LIKE ? OR
            u.bank_id LIKE ? OR
            a.account_number LIKE ?
        )
        LIMIT 20
    ";
    
    // Gabungkan parameter untuk unit scope dan search term
    $params = array_merge($unit_scope, [$search_term, $search_term, $search_term, $search_term, $search_term]);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $results]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Pencarian gagal: ' . $e->getMessage()]);
}
