<?php
// File: app/user_update_preference.php
// Penjelasan: Pengguna menyimpan preferensi aplikasi (misal: bahasa).

require_once 'auth_middleware.php';

/*
    CATATAN DATABASE: Menambahkan kolom `preferences` ke tabel `customer_profiles`.
    ALTER TABLE `customer_profiles` ADD `preferences` JSON NULL DEFAULT NULL AFTER `ktp_image_path`;
*/

$input = json_decode(file_get_contents('php://input'), true);
$preference_key = $input['key'] ?? null;
$preference_value = $input['value'] ?? null;

if (empty($preference_key) || is_null($preference_value)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Key dan value preferensi wajib diisi.']);
    exit();
}

try {
    // 1. Ambil preferensi saat ini
    $stmt_get = $pdo->prepare("SELECT preferences FROM customer_profiles WHERE user_id = ?");
    $stmt_get->execute([$authenticated_user_id]);
    $current_prefs_json = $stmt_get->fetchColumn();
    
    $preferences = $current_prefs_json ? json_decode($current_prefs_json, true) : [];
    
    // 2. Perbarui atau tambahkan key baru
    $preferences[$preference_key] = $preference_value;
    
    // 3. Simpan kembali ke database
    $new_prefs_json = json_encode($preferences);
    $stmt_update = $pdo->prepare("UPDATE customer_profiles SET preferences = ? WHERE user_id = ?");
    $stmt_update->execute([$new_prefs_json, $authenticated_user_id]);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Preferensi berhasil diperbarui.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui preferensi: ' . $e->getMessage()]);
}
?>
