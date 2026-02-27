<?php
// File: app/admin_get_deposit_products.php
// Penjelasan: Admin mengambil daftar semua produk deposito.

require_once 'auth_middleware.php';

// Hanya peran manajerial yang bisa mengakses
if ($authenticated_user_role_id > 3) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

try {
    $stmt = $pdo->query("SELECT * FROM deposit_products ORDER BY tenor_months ASC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $products]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data produk deposito.']);
}
