<?php
// File: app/admin_loan_products_get.php
// Penjelasan: Mengambil daftar semua produk pinjaman.
// REVISI: Menggunakan kolom tenor dan tenor_unit yang baru.

require_once 'auth_middleware.php';

// Semua staf bisa melihat, kecuali nasabah
if ($authenticated_user_role_id > 8) { 
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

try {
    // REVISI: Mengambil semua kolom yang relevan termasuk yang baru
    $stmt = $pdo->query("SELECT id, product_name, min_amount, max_amount, interest_rate_pa, min_tenor, max_tenor, tenor_unit, late_payment_fee, is_active FROM loan_products ORDER BY product_name ASC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $products]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data produk pinjaman.']);
}
?>
