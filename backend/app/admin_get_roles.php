<?php
// File: app/admin_get_roles.php
// Penjelasan: Admin melihat daftar semua peran pengguna.

require_once 'auth_middleware.php';

$allowed_roles = [1, 2, 3]; // Kepala Unit ke atas
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

try {
    // PERBAIKAN: Menggunakan nama tabel 'roles' dan menghapus kolom 'description'
    $stmt = $pdo->query("SELECT id, role_name FROM roles ORDER BY id");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $roles]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data peran.']);
}
?>
