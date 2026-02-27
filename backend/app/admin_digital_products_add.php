<?php
// File: app/admin_digital_products_add.php
// Penjelasan: Admin menambahkan produk digital baru.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: Kepala Unit ke atas
$allowed_roles = [1, 2, 3];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$required = ['biller_code', 'product_code', 'product_name', 'price'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}

try {
    $stmt = $pdo->prepare("INSERT INTO digital_products (biller_code, product_code, product_name, price) VALUES (?, ?, ?, ?)");
    $stmt->execute([$input['biller_code'], $input['product_code'], $input['product_name'], $input['price']]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Produk digital berhasil ditambahkan.']);

} catch (PDOException $e) {
    // Cek jika error karena duplikat
    if ($e->errorInfo[1] == 1062) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Kode Produk sudah ada.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan produk.']);
    }
}
?>
