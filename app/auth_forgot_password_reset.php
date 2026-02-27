<?php
// File: app/auth_forgot_password_reset.php
// Penjelasan: Memvalidasi token dan mengatur ulang password pengguna.

require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);

// Validasi
if (!isset($input['token']) || !isset($input['new_password'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Token dan password baru wajib diisi.']);
    exit();
}
if (strlen($input['new_password']) < 8) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Password baru minimal 8 karakter.']);
    exit();
}

$token = $input['token'];
$new_password = $input['new_password'];

try {
    // 1. Cari token di database
    $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $reset_request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset_request) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Token tidak valid.']);
        exit();
    }
    
    // 2. Cek apakah token kedaluwarsa
    if (strtotime($reset_request['expires_at']) < time()) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Token telah kedaluwarsa. Silakan minta token baru.']);
        exit();
    }

    $email = $reset_request['email'];
    $pdo->beginTransaction();

    // 3. Hash dan update password baru di tabel users
    $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt_update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $stmt_update->execute([$new_password_hash, $email]);

    // 4. Hapus token dari tabel resets
    $stmt_delete = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt_delete->execute([$email]);

    $pdo->commit();

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Password Anda telah berhasil direset. Silakan login.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
}
?>
