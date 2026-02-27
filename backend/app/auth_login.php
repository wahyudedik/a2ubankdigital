<?php
// File: app/auth_login.php
// Penjelasan: Memproses login pengguna, mengembalikan data pengguna dan token JWT.
// REVISI: Memberikan pesan error yang lebih spesifik untuk status akun yang berbeda.

require_once 'config.php'; 
use Firebase\JWT\JWT;

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email dan password wajib diisi.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, bank_id, role_id, full_name, email, password_hash, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // --- LOGIKA BARU: Cek Status Akun ---
        if ($user['status'] === 'BLOCKED') {
            http_response_code(403); // Forbidden
            throw new Exception('Akun Anda diblokir. Silakan hubungi Customer Service.');
        }
        if ($user['status'] === 'PENDING_VERIFICATION') {
            http_response_code(403);
            throw new Exception('Akun Anda belum aktif. Silakan cek email Anda untuk verifikasi OTP.');
        }
        if ($user['status'] !== 'ACTIVE') {
            http_response_code(403);
            throw new Exception('Akun Anda tidak aktif. Hubungi Customer Service untuk informasi lebih lanjut.');
        }
        // ------------------------------------

        $stmt_reset_attempts = $pdo->prepare("UPDATE users SET failed_login_attempts = 0 WHERE id = ?");
        $stmt_reset_attempts->execute([$user['id']]);

        $secret_key = $_ENV['JWT_SECRET'];
        $issuedat_claim = time();
        $expire_claim = $issuedat_claim + (3600 * 24);

        $token_payload = [
            "iss" => $_ENV['JWT_ISSUER'], "aud" => $_ENV['JWT_AUDIENCE'],
            "iat" => $issuedat_claim, "exp" => $expire_claim,
            "data" => [ "user_id" => $user['id'], "role_id" => $user['role_id'] ]
        ];
        $jwt = JWT::encode($token_payload, $secret_key, 'HS256');

        $user_data_for_frontend = [
            'id' => $user['id'], 'bankId' => $user['bank_id'],
            'roleId' => $user['role_id'], 'fullName' => $user['full_name'],
            'email' => $user['email']
        ];

        http_response_code(200);
        echo json_encode([
            'status' => 'success', 'message' => 'Login berhasil.',
            'token' => $jwt, 'user' => $user_data_for_frontend
        ]);

    } else {
        if ($user) {
            $stmt_fail = $pdo->prepare("UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?");
            $stmt_fail->execute([$user['id']]);
        }
        http_response_code(401); // Unauthorized
        throw new Exception('Email atau password salah3.');
    }

} catch (Exception $e) {
    // Gunakan HTTP code yang sudah di-set sebelumnya, atau default ke 401
    $code = http_response_code() >= 400 ? http_response_code() : 401;
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
