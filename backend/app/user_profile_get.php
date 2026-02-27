<?php
// File: app/user_profile_get.php
// Penjelasan: Mengambil data profil lengkap dari pengguna yang sedang login.

require_once 'auth_middleware.php'; // Otomatis memanggil config.php dan memvalidasi token

try {
    // $authenticated_user_id tersedia dari auth_middleware.php
    $stmt = $pdo->prepare("
        SELECT 
            u.bank_id,
            u.full_name,
            u.email,
            u.phone_number,
            u.last_login_at,
            cp.nik,
            cp.mother_maiden_name,
            cp.pob,
            cp.dob,
            cp.gender,
            cp.address_ktp,
            cp.address_domicile,
            cp.occupation,
            cp.kyc_status
        FROM users u
        LEFT JOIN customer_profiles cp ON u.id = cp.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$authenticated_user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Profil pengguna tidak ditemukan.']);
        exit();
    }

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $profile]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()]);
}

?>
