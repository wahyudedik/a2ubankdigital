<?php
// File: app/admin_get_units.php
// Penjelasan: Endpoint untuk mengambil daftar semua cabang dan unit di bawahnya.
// REVISI: Memastikan semua kolom yang relevan dan format output selalu array.

require_once 'auth_middleware.php';

// Hanya Super Admin dan Kepala Cabang yang butuh akses penuh ke struktur
if ($authenticated_user_role_id > 2) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

try {
    // REVISI: Query sekarang secara eksplisit memilih semua kolom yang dibutuhkan oleh modal.
    $stmt = $pdo->query("SELECT id, unit_name, unit_type, parent_id, address, latitude, longitude, is_active FROM units ORDER BY parent_id, unit_name");
    $all_units = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $branches = [];
    $units_map = [];

    // Pisahkan antara cabang (parent_id IS NULL) dan unit
    foreach ($all_units as $unit) {
        // Pastikan tipe data konsisten untuk frontend
        $unit['id'] = (int)$unit['id'];
        $unit['parent_id'] = $unit['parent_id'] ? (int)$unit['parent_id'] : null;
        $unit['is_active'] = (int)$unit['is_active'];

        if ($unit['parent_id'] === null) {
            $branches[$unit['id']] = [
                'id' => $unit['id'],
                'unit_name' => $unit['unit_name'],
                'unit_type' => $unit['unit_type'],
                'is_active' => $unit['is_active'],
                'address' => $unit['address'],
                'latitude' => $unit['latitude'],
                'longitude' => $unit['longitude'],
                'units' => []
            ];
        } else {
            // Kelompokkan unit berdasarkan parent_id (ID cabang)
            $units_map[$unit['parent_id']][] = $unit;
        }
    }

    // Masukkan unit ke dalam cabang yang sesuai
    foreach ($units_map as $branch_id => $units) {
        if (isset($branches[$branch_id])) {
            $branches[$branch_id]['units'] = $units;
        }
    }

    // REVISI KRUSIAL: Gunakan array_values() untuk mengubah object menjadi array
    // Ini memastikan frontend selalu menerima array dan .map() tidak akan error.
    $response_data = array_values($branches);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $response_data]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data unit: ' . $e->getMessage()]);
}
