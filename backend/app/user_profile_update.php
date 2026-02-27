<?php
// File: app/user_profile_update.php
// Penjelasan: Memperbarui data profil pengguna yang sedang login.

require_once 'auth_middleware.php';

$input = json_decode(file_get_contents('php://input'), true);

// Daftar kolom yang diizinkan untuk di-update
$allowed_user_fields = ['phone_number'];
$allowed_profile_fields = ['address_domicile', 'occupation'];

$user_updates = [];
$profile_updates = [];
$params = [];

// Memisahkan data untuk tabel 'users' dan 'customer_profiles'
foreach ($input as $key => $value) {
    if (in_array($key, $allowed_user_fields)) {
        $user_updates[] = "$key = ?";
        $params[$key] = $value;
    }
    if (in_array($key, $allowed_profile_fields)) {
        $profile_updates[] = "$key = ?";
        $params[$key] = $value;
    }
}

if (empty($user_updates) && empty($profile_updates)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tidak ada data valid yang dikirim untuk diperbarui.']);
    exit();
}

try {
    $pdo->beginTransaction();

    if (!empty($user_updates)) {
        $sql = "UPDATE users SET " . implode(', ', $user_updates) . " WHERE id = ?";
        $stmt_params = array_values(array_intersect_key($params, array_flip($allowed_user_fields)));
        $stmt_params[] = $authenticated_user_id;
        $pdo->prepare($sql)->execute($stmt_params);
    }

    if (!empty($profile_updates)) {
        $sql = "UPDATE customer_profiles SET " . implode(', ', $profile_updates) . " WHERE user_id = ?";
        $stmt_params = array_values(array_intersect_key($params, array_flip($allowed_profile_fields)));
        $stmt_params[] = $authenticated_user_id;
        $pdo->prepare($sql)->execute($stmt_params);
    }
    
    $pdo->commit();

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Profil berhasil diperbarui.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui profil: ' . $e->getMessage()]);
}
?>
