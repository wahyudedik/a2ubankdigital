<?php
// File: app/utility_get_investment_products.php
// Penjelasan: Mengambil daftar produk investasi.

require_once 'config.php';


try {
    $stmt = $pdo->query("SELECT product_name, product_code, category, description, risk_profile, minimum_investment FROM investment_products WHERE is_active = 1");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $products]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data produk investasi.']);
}
?>
