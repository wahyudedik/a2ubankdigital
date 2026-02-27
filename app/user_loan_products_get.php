<?php
// File: app/user_loan_products_get.php
// Penjelasan: Nasabah mengambil daftar produk pinjaman yang aktif.
// REVISI: Menggunakan kolom tenor dan tenor_unit yang baru.

require_once 'auth_middleware.php';

// Pastikan hanya nasabah yang bisa akses
if ($authenticated_user_role_id != 9) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

try {
    // REVISI: Mengambil kolom tenor, min_tenor, max_tenor, dan tenor_unit
    $stmt = $pdo->query("SELECT id, product_name, min_amount, max_amount, interest_rate_pa, min_tenor, max_tenor, tenor_unit FROM loan_products WHERE is_active = 1 ORDER BY product_name ASC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $products]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data produk pinjaman.']);
}
?>
