<?php
// File: app/admin_add_deposit_product.php
// Penjelasan: Admin menambahkan produk deposito baru.

require_once 'auth_middleware.php';

// Hanya Kepala Unit ke atas
if ($authenticated_user_role_id > 3) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

// Validasi
$required = ['product_name', 'interest_rate_pa', 'tenor_months', 'min_amount'];
foreach ($required as $field) {
    if (!isset($input[$field]) || $input[$field] === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}

try {
    $sql = "INSERT INTO deposit_products (product_name, interest_rate_pa, tenor_months, min_amount, is_active) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $input['product_name'],
        $input['interest_rate_pa'],
        $input['tenor_months'],
        $input['min_amount'],
        $input['is_active'] ?? 1
    ]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Produk deposito baru berhasil ditambahkan.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan produk: ' . $e->getMessage()]);
}
