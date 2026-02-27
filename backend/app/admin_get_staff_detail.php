<?php
// File: app/admin_get_staff_detail.php
// Penjelasan: Endpoint baru untuk mengambil detail satu staf spesifik untuk form edit.

require_once 'auth_middleware.php';

// Hanya Super Admin dan Kepala Cabang yang bisa
$allowed_roles = [1, 2];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$staff_id = $_GET['id'] ?? null;
if (!$staff_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Staf wajib diisi.']);
    exit();
}

try {
    $sql = "SELECT id, full_name, email, role_id, unit_id, status FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Staf tidak ditemukan.']);
        exit();
    }
    
    // Validasi hierarki: Pastikan admin tidak mengakses data staf di atas levelnya
    if ($staff['role_id'] <= $authenticated_user_role_id && $authenticated_user_role_id !== 1) {
         http_response_code(403);
         echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
         exit();
    }
    
    // Validasi lingkup unit jika bukan Super Admin
    if ($authenticated_user_role_id !== 1 && !in_array($staff['unit_id'], $accessible_unit_ids)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Akses ditolak: Staf ini tidak berada di dalam lingkup unit Anda.']);
        exit();
    }

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $staff]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data staf.']);
}
