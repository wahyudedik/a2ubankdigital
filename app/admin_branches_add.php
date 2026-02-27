<?php
// File: app/admin_branches_add.php
// Penjelasan: Admin menambahkan data cabang atau ATM baru.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: Super Admin, Kepala Cabang
$allowed_roles = [1, 2];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$required = ['branch_name', 'address', 'latitude', 'longitude', 'type'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO bank_branches (branch_name, address, latitude, longitude, type, operational_hours) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $input['branch_name'],
        $input['address'],
        $input['latitude'],
        $input['longitude'],
        $input['type'],
        $input['operational_hours'] ?? null
    ]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Cabang/ATM baru berhasil ditambahkan.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan data: ' . $e->getMessage()]);
}
?>
