<?php
// File: app/admin_update_unit.php
// Penjelasan: Endpoint untuk memperbarui data cabang atau unit.
// REVISI: Menambahkan field address, latitude, dan longitude.

require_once 'auth_middleware.php';

if ($authenticated_user_role_id > 2) { // Hanya Super Admin & KaCab
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$unit_id = $input['id'] ?? null;

if (!$unit_id || empty($input['unit_name']) || empty($input['unit_type'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID, Nama, dan Jenis wajib diisi.']);
    exit();
}

if ($input['unit_type'] === 'UNIT' && empty($input['parent_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Unit harus berada di bawah sebuah Cabang.']);
    exit();
}

try {
    // REVISI: Menambahkan kolom baru ke query UPDATE
    $sql = "UPDATE units SET 
                unit_name = ?, 
                unit_type = ?, 
                parent_id = ?, 
                address = ?, 
                latitude = ?, 
                longitude = ?, 
                is_active = ? 
            WHERE id = ?";
            
    $stmt = $pdo->prepare($sql);
    
    $parent_id = ($input['unit_type'] === 'CABANG') ? null : $input['parent_id'];
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
        $input['is_active'],
        $unit_id
    ]);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Data Unit/Cabang berhasil diperbarui.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data: ' . $e->getMessage()]);
}
