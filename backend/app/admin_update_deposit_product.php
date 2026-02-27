<?php
// File: app/admin_update_deposit_product.php
// Penjelasan: Admin memperbarui produk deposito.

require_once 'auth_middleware.php';

// Hanya Kepala Unit ke atas
if ($authenticated_user_role_id > 3) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = $input['id'] ?? 0;

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Produk tidak valid.']);
    exit();
}

try {
    $sql = "UPDATE deposit_products SET 
                product_name = ?, 
                interest_rate_pa = ?, 
                tenor_months = ?, 
                min_amount = ?,
                is_active = ?
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $input['product_name'],
        $input['interest_rate_pa'],
        $input['tenor_months'],
        $input['min_amount'],
        $input['is_active'],
        $product_id
    ]);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Produk deposito berhasil diperbarui.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui produk: ' . $e->getMessage()]);
}
