<?php
// File: app/user_security_update_password.php
// Penjelasan: Mengubah password login pengguna.

require_once 'auth_middleware.php';

$input = json_decode(file_get_contents('php://input'), true);

// Validasi
if (!isset($input['current_password']) || !isset($input['new_password'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Password saat ini dan password baru wajib diisi.']);
    exit();
}
if (strlen($input['new_password']) < 8) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Password baru minimal 8 karakter.']);
    exit();
}

$current_password = $input['current_password'];
$new_password = $input['new_password'];

try {
    // 1. Ambil hash password saat ini
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$authenticated_user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Verifikasi password saat ini
    if (!$user || !password_verify($current_password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Password saat ini yang Anda masukkan salah.']);
        exit();
    }

    // 3. Hash password baru dan perbarui
    $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt_update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt_update->execute([$new_password_hash, $authenticated_user_id]);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Password berhasil diperbarui.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
}

?>
