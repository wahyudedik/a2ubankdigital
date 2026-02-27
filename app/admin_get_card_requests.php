<?php
// File: app/admin_get_card_requests.php
// Penjelasan: Admin mengambil daftar pengajuan kartu dari nasabah.
// REVISI: Menerapkan data scoping berdasarkan unit staf.

require_once 'auth_middleware.php';

// Hanya staf yang berwenang (misal: CS ke atas)
if ($authenticated_user_role_id > 6) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

try {
    $base_sql = "
        SELECT 
            c.id, 
            u.full_name as customer_name, 
            a.account_number,
            c.status,
            c.requested_at
        FROM cards c
        JOIN users u ON c.user_id = u.id
        JOIN customer_profiles cp ON u.id = cp.user_id
        JOIN accounts a ON c.account_id = a.id
        WHERE c.status = 'REQUESTED'
    ";
    $params = [];

    // LOGIKA BARU: Terapkan Data Scoping berdasarkan unit kerja staf.
    if ($authenticated_user_role_id !== 1) {
        if (empty($accessible_unit_ids)) {
            echo json_encode(['status' => 'success', 'data' => []]);
            exit();
        }
        $placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        $base_sql .= " AND cp.unit_id IN ($placeholders)";
        $params = array_merge($params, $accessible_unit_ids);
    }

    $base_sql .= " ORDER BY c.requested_at ASC";

    $stmt = $pdo->prepare($base_sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $requests]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data pengajuan kartu.']);
}
