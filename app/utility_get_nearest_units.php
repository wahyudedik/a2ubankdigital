<?php
// File: app/utility_get_nearest_units.php
// Penjelasan: Endpoint publik untuk menemukan unit/cabang terdekat.
// REVISI: Sekarang mencari CABANG sebagai fallback jika UNIT tidak ditemukan.

require_once 'config.php';

$lat = $_GET['lat'] ?? null;
$lon = $_GET['lon'] ?? null;

if ($lat === null || $lon === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Parameter latitude (lat) dan longitude (lon) wajib diisi.']);
    exit();
}

try {
    // --- Langkah 1: Coba cari UNIT terdekat terlebih dahulu ---
    // Rumus Haversine untuk menghitung jarak
    $sql_unit = "
        SELECT 
            id, unit_name, 'UNIT' as type,
            ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance 
        FROM units 
        WHERE is_active = 1 AND unit_type = 'UNIT'
        HAVING distance < 100 
        ORDER BY distance 
        LIMIT 5
    ";

    $stmt_unit = $pdo->prepare($sql_unit);
    $stmt_unit->execute([$lat, $lon, $lat]);
    $locations = $stmt_unit->fetchAll(PDO::FETCH_ASSOC);

    // --- Langkah 2: Jika tidak ada UNIT ditemukan, cari CABANG terdekat ---
    if (empty($locations)) {
        $sql_branch = "
            SELECT 
                id, unit_name, 'CABANG' as type,
                ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance 
            FROM units 
            WHERE is_active = 1 AND unit_type = 'CABANG'
            HAVING distance < 500 -- Radius lebih besar untuk cabang
            ORDER BY distance 
            LIMIT 5
        ";
        $stmt_branch = $pdo->prepare($sql_branch);
        $stmt_branch->execute([$lat, $lon, $lat]);
        $locations = $stmt_branch->fetchAll(PDO::FETCH_ASSOC);
    }

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $locations]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data lokasi terdekat: ' . $e->getMessage()]);
}
