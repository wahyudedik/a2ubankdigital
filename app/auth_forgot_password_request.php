<?php
// File: app/auth_forgot_password_request.php
// Penjelasan: Memproses permintaan lupa password dengan mengirimkan token reset ke email pengguna.

require_once 'config.php';
require_once __DIR__ . '/helpers/email_helper.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['email']) || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email valid wajib diisi.']);
    exit();
}

$email = $input['email'];

/*
    CATATAN DATABASE:
    Endpoint ini memerlukan tabel `password_resets`.
    Struktur:
    CREATE TABLE `password_resets` (
      `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `email` varchar(255) NOT NULL,
      `token` varchar(255) NOT NULL,
      `expires_at` timestamp NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `email` (`email`),
      KEY `token` (`token`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/

try {
    // 1. Cek apakah pengguna dengan email ini ada dan aktif
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ? AND status = 'ACTIVE'");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Kirim respons sukses meskipun email tidak ada, untuk mencegah user enumeration.
    if (!$user) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Jika email Anda terdaftar, kami telah mengirimkan instruksi reset password.']);
        exit();
    }

    // 2. Buat token reset yang aman
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', time() + (60 * 60)); // Berlaku 1 jam

    // 3. Hapus token lama & simpan token baru
    $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
    $stmt_insert = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt_insert->execute([$email, $token, $expires_at]);

    // 4. Kirim email
    $reset_link = "https://a2ubankdigital.my.id/reset-password?token=" . $token; // URL di frontend
    $template_data = [
        'preheader' => 'Instruksi untuk mereset password Anda.',
        'full_name' => $user['full_name'],
        'reset_link' => $reset_link
    ];

    send_email($email, $user['full_name'], 'Reset Password Akun Bank Anda', 'reset_password_template', $template_data);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Jika email Anda terdaftar, kami telah mengirimkan instruksi reset password.']);

} catch (Exception $e) {
    // Jangan ekspos error detail ke user
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
}
?>