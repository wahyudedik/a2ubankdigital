<?php
// File: app/admin_digital_products_update.php
// Penjelasan: Admin memperbarui produk digital.

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
    // Dapatkan field yang akan diupdate
    $update_fields = [];
    $params = [];
    $allowed_fields = ['product_name', 'price', 'is_active'];

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
    
    $params[] = $product_id;
    $sql = "UPDATE digital_products SET " . implode(', ', $update_fields) . " WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Produk digital berhasil diperbarui.']);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Produk tidak ditemukan.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui produk.']);
}
?>
