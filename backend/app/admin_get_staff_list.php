<?php
// File: app/admin_get_staff_list.php
// Penjelasan: Mengambil daftar staf dan menambahkan flag `can_edit`.
// REVISI: Menambahkan flag `can_edit` untuk mempermudah logika di frontend.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: Super Admin, Kepala Cabang, Kepala Unit
$allowed_roles = [1, 2, 3];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

try {
    $sql = "
        SELECT 
            u.id, 
            u.full_name, 
            u.email, 
            u.status, 
            u.unit_id,
            u.role_id,
            r.role_name,
            un.unit_name as unit_name,
            b.unit_name as branch_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        LEFT JOIN units un ON u.unit_id = un.id
        LEFT JOIN units b ON un.parent_id = b.id
        WHERE u.role_id != 9 AND u.role_id > ?
        ORDER BY b.unit_name, un.unit_name, u.full_name
    ";
    
    $stmt = $pdo->prepare($sql);
    $role_to_view = ($authenticated_user_role_id == 1) ? 0 : $authenticated_user_role_id;
    $stmt->execute([$role_to_view]);
    $staff_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tambahkan flag `can_edit`
    foreach($staff_list as &$staff) {
        $can_edit_flag = false;
        // Super Admin bisa edit semua di bawahnya
        if ($authenticated_user_role_id === 1 && $staff['role_id'] > 1) {
            $can_edit_flag = true;
        }
        // Kepala Cabang bisa edit semua di bawahnya yang ada dalam lingkup unitnya
        elseif ($authenticated_user_role_id === 2 && $staff['role_id'] > 2 && in_array($staff['unit_id'], $accessible_unit_ids)) {
             $can_edit_flag = true;
        }
        $staff['can_edit'] = $can_edit_flag;
    }

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $staff_list]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil daftar staf: ' . $e->getMessage()]);
}
