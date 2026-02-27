<?php
// File: app/admin_loan_products_edit.php
// Penjelasan: Admin memperbarui detail produk pinjaman.
// REVISI: Menambahkan kolom `late_payment_fee` dan `tenor_unit` ke dalam query UPDATE.

require_once 'auth_middleware.php';

if ($authenticated_user_role_id > 3) { // Hanya Kepala Unit ke atas
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
    // REVISI: Menggunakan nama kolom dan field baru, termasuk late_payment_fee
    $sql = "UPDATE loan_products SET 
                product_name = ?, 
                min_amount = ?, 
                max_amount = ?, 
                interest_rate_pa = ?, 
                min_tenor = ?, 
                max_tenor = ?,
                tenor_unit = ?,
                late_payment_fee = ?,
                is_active = ?
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $input['product_name'],
        $input['min_amount'],
        $input['max_amount'],
        $input['interest_rate_pa'],
        $input['min_tenor'],
        $input['max_tenor'],
        $input['tenor_unit'],
        $input['late_payment_fee'], // <-- Baris yang hilang ditambahkan di sini
        $input['is_active'],
        $product_id
    ]);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Produk pinjaman berhasil diperbarui.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui produk: ' . $e->getMessage()]);
}
?>

