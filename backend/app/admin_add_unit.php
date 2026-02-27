<?php
// File: app/admin_add_unit.php
// Penjelasan: Endpoint untuk menambah cabang atau unit baru.
// REVISI: Menambahkan penanganan untuk field opsional (address, lat, lon) khusus untuk Cabang.

require_once 'auth_middleware.php';

if ($authenticated_user_role_id > 2) { // Hanya Super Admin & KaCab
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

// Validasi dasar
if (empty($input['unit_name']) || empty($input['unit_type'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nama dan Jenis wajib diisi.']);
    exit();
}

if ($input['unit_type'] === 'UNIT' && empty($input['parent_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Unit harus berada di bawah sebuah Cabang.']);
    exit();
}

try {
    // REVISI: Query INSERT sekarang menyertakan semua kolom yang relevan.
    $sql = "INSERT INTO units (unit_name, unit_type, parent_id, address, latitude, longitude, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    // Menentukan nilai berdasarkan unit_type
    $parent_id = ($input['unit_type'] === 'CABANG') ? null : ($input['parent_id'] ?? null);
    $address = ($input['unit_type'] === 'CABANG') ? ($input['address'] ?? null) : null;
    $latitude = ($input['unit_type'] === 'CABANG') ? ($input['latitude'] ?? null) : null;
    $longitude = ($input['unit_type'] === 'CABANG') ? ($input['longitude'] ?? null) : null;
    
    $stmt->execute([
        $input['unit_name'],
        $input['unit_type'],
        $parent_id,
        $address,
        $latitude,
        $longitude,
        $input['is_active'] ?? 1
    ]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Unit/Cabang baru berhasil ditambahkan.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan data: ' . $e->getMessage()]);
}
