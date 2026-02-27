<?php
// File: app/admin_branches_update.php
// Penjelasan: Admin memperbarui data cabang atau ATM.

require_once 'auth_middleware.php';

$allowed_roles = [1, 2];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$branch_id = $input['id'] ?? null;
if (!$branch_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Cabang wajib diisi.']);
    exit();
}

try {
    $update_fields = [];
    $params = [];
    $allowed_fields = ['branch_name', 'address', 'latitude', 'longitude', 'type', 'operational_hours', 'is_active'];

    foreach ($allowed_fields as $field) {
        if (isset($input[$field])) {
            $update_fields[] = "$field = ?";
            $params[] = $input[$field];
        }
    }
    if (empty($update_fields)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada data untuk diperbarui.']);
        exit();
    }
    
    $params[] = $branch_id;
    $sql = "UPDATE bank_branches SET " . implode(', ', $update_fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Data cabang berhasil diperbarui.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data: ' . $e->getMessage()]);
}
?>
