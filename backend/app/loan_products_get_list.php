<?php
// File: app/loan_products_get_list.php
// Penjelasan: Mengambil daftar produk pinjaman yang tersedia.

require_once 'auth_middleware.php';

/*
    CATATAN DATABASE: Memerlukan tabel `loan_products`.
    CREATE TABLE `loan_products` (
      `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `product_name` varchar(255) NOT NULL,
      `min_amount` decimal(15,2) NOT NULL,
      `max_amount` decimal(15,2) NOT NULL,
      `interest_rate_monthly` decimal(5,2) NOT NULL, -- Bunga per bulan
      `max_tenor_months` int(11) NOT NULL,
      `description` text,
      `is_active` tinyint(1) NOT NULL DEFAULT 1,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB;
*/

try {
    $stmt = $pdo->prepare("SELECT id, product_name, min_amount, max_amount, interest_rate_monthly, max_tenor_months, description FROM loan_products WHERE is_active = 1");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $products]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data produk pinjaman.']);
}
?>
