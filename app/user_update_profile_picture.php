<?php
// File: app/user_update_profile_picture.php
// Penjelasan: Nasabah mengubah foto profilnya.

require_once 'auth_middleware.php';

$upload_dir = __DIR__ . '/uploads/profiles/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if (isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_name = 'user_' . $authenticated_user_id . '.' . $file_extension;
    $target_path = $upload_dir . $file_name;
    $web_path = '/app/uploads/profiles/' . $file_name;

    // Hapus file lama jika ada
    $old_files = glob($upload_dir . 'user_' . $authenticated_user_id . '.*');
    foreach($old_files as $old_file) {
        unlink($old_file);
    }

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET profile_picture_url = ? WHERE id = ?");
            $stmt->execute([$web_path, $authenticated_user_id]);

            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Foto profil berhasil diperbarui.', 'profile_picture_url' => $web_path]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan URL foto.']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah foto.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tidak ada foto yang diunggah.']);
}
?>
