<?php
// File: app/admin_digital_products_delete.php
// Penjelasan: Admin melakukan soft-delete pada produk digital.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: Kepala Unit ke atas
$allowed_roles = [1, 2, 3];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = $input['id'] ?? null;
if (!$product_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Produk wajib diisi.']);
    exit();
}

try {
    // Soft delete dengan mengubah status is_active
    $stmt = $pdo->prepare("UPDATE digital_products SET is_active = 0 WHERE id = ?");
    $stmt->execute([$product_id]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Produk digital berhasil dinonaktifkan.']);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Produk tidak ditemukan.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menonaktifkan produk.']);
}
?>
