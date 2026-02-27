<?php
// File: app/admin_loan_products_add.php
// Penjelasan: Admin menambahkan produk pinjaman baru.
// REVISI: Menggunakan kolom tenor dan tenor_unit yang baru.

require_once 'auth_middleware.php';

if ($authenticated_user_role_id > 3) { // Hanya Kepala Unit ke atas
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

// REVISI: Validasi menggunakan nama kolom baru dan tenor_unit
$required = ['product_name', 'min_amount', 'max_amount', 'interest_rate_pa', 'min_tenor', 'max_tenor', 'tenor_unit'];
foreach ($required as $field) {
    if (!isset($input[$field]) || $input[$field] === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}

// Validasi tambahan untuk tenor_unit
if (!in_array($input['tenor_unit'], ['HARI', 'MINGGU', 'BULAN'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Satuan tenor tidak valid.']);
    exit();
}


try {
    // REVISI: Query INSERT disesuaikan dengan kolom baru
    $sql = "INSERT INTO loan_products (product_name, min_amount, max_amount, interest_rate_pa, min_tenor, max_tenor, tenor_unit) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $input['product_name'],
        $input['min_amount'],
        $input['max_amount'],
        $input['interest_rate_pa'],
        $input['min_tenor'],
        $input['max_tenor'],
        $input['tenor_unit']
    ]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Produk pinjaman baru berhasil ditambahkan.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan produk: ' . $e->getMessage()]);
}
?>

