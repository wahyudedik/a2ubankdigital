<?php
// File: app/deposit_products_get_list.php
// Penjelasan: Mengambil daftar produk deposito yang tersedia.

require_once 'auth_middleware.php';


try {
    $stmt = $pdo->prepare("SELECT id, product_name, tenor_months, interest_rate_pa, min_amount FROM deposit_products WHERE is_active = 1");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $products]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data produk deposito.']);
}
?>
