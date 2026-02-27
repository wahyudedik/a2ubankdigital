<?php
// File: app/digital_products_get_list.php
// Penjelasan: Mengambil daftar produk digital yang tersedia untuk dibeli.

require_once 'auth_middleware.php';

$category = $_GET['category'] ?? null;

/*
    CATATAN DATABASE:
    Endpoint ini memerlukan tabel `digital_products`.
    Struktur:
    CREATE TABLE `digital_products` (
      `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `product_code` varchar(50) NOT NULL,
      `product_name` varchar(255) NOT NULL,
      `category` enum('PULSA','DATA','TOKEN_LISTRIK','E_WALLET') NOT NULL,
      `provider` varchar(50) DEFAULT NULL,
      `price` decimal(12,2) NOT NULL,
      `is_active` tinyint(1) NOT NULL DEFAULT 1,
      PRIMARY KEY (`id`),
      UNIQUE KEY `product_code` (`product_code`)
    ) ENGINE=InnoDB;
    
    -- Contoh Data --
    INSERT INTO `digital_products` (`product_code`, `product_name`, `category`, `provider`, `price`) VALUES
    ('TSEL5', 'Telkomsel 5.000', 'PULSA', 'Telkomsel', 5500.00),
    ('PLN20', 'Token Listrik 20.000', 'TOKEN_LISTRIK', 'PLN', 20500.00);
*/

try {
    if ($category) {
        $stmt = $pdo->prepare("SELECT product_code, product_name, category, provider, price FROM digital_products WHERE is_active = 1 AND category = ? ORDER BY price");
        $stmt->execute([$category]);
    } else {
        $stmt = $pdo->prepare("SELECT product_code, product_name, category, provider, price FROM digital_products WHERE is_active = 1 ORDER BY category, price");
        $stmt->execute();
    }
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $products]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
}
?>
