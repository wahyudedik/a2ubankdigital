<?php
// File: app/auth_login_2fa_verify.php
// Penjelasan: Memverifikasi kode 2FA setelah login password berhasil.

require_once 'config.php'; // Tidak pakai middleware
require_once 'vendor/autoload.php';

use Firebase\JWT\JWT;
use OTPHP\TOTP;

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? null;
$code = $input['code'] ?? null;

if (!$user_id || !$code) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Input tidak lengkap.']);
    exit();
}

try {
    // Ambil detail user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_totp_enabled = 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) throw new Exception("User tidak ditemukan atau 2FA tidak aktif.");

    // Verifikasi kode
    $otp = TOTP::createFromSecret($user['totp_secret']);
    if (!$otp->verify($code)) {
        throw new Exception("Kode 2FA tidak valid.");
    }
    
    // Jika valid, generate JWT seperti di auth_login.php
    $issued_at = time();
    $expiration_time = $issued_at + (60 * 60 * 8); // 8 jam
    $payload = [
        'iss' => $_ENV['JWT_ISSUER'],
        'aud' => $_ENV['JWT_AUDIENCE'],
        'iat' => $issued_at,
        'exp' => $expiration_time,
        'data' => [
            'id' => $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role_id' => $user['role_id'],
        ]
    ];
    $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Login berhasil.', 'token' => $jwt]);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
