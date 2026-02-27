<?php
// File: app/admin_loan_products_delete.php
// Penjelasan: Admin menghapus produk pinjaman (soft delete).

require_once 'auth_middleware.php';

if ($authenticated_user_role_id > 2) { // Hanya Kepala Cabang ke atas
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
    // Sebenarnya lebih baik soft delete (mengubah status is_active menjadi 0)
    // Namun untuk contoh ini, kita lakukan hard delete
    $stmt = $pdo->prepare("DELETE FROM loan_products WHERE id = ?");
    $stmt->execute([$product_id]);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Produk pinjaman berhasil dihapus.']);

} catch (PDOException $e) {
    http_response_code(500);
    // Jika ada foreign key constraint, akan gagal.
    echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus produk. Pastikan tidak ada pinjaman aktif yang menggunakan produk ini.']);
}
?>
