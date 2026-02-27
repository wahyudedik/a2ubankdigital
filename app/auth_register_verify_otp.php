<?php
// File: app/auth_register_verify_otp.php
// Penjelasan: Memverifikasi OTP, mengaktifkan akun, dan membuat rekening.
// REVISI: Menambahkan validasi input yang lebih spesifik untuk debugging.

require_once 'config.php';
require_once __DIR__ . '/helpers/notification_helper.php';

// Fungsi helper untuk membuat JWT
function create_jwt_token($user_id, $role_id) {
    $secret_key = $_ENV['JWT_SECRET'];
    $issuer_claim = $_ENV['JWT_ISSUER'];
    $audience_claim = $_ENV['JWT_AUDIENCE'];
    $issuedat_claim = time();
    $expire_claim = $issuedat_claim + (3600 * 24);

    $token_payload = [
        "iss" => $issuer_claim,
        "aud" => $audience_claim,
        "iat" => $issuedat_claim,
        "exp" => $expire_claim,
        "data" => ["user_id" => $user_id, "role_id" => $role_id]
    ];
    return Firebase\JWT\JWT::encode($token_payload, $secret_key, 'HS256');
}

$input = json_decode(file_get_contents('php://input'), true);

// PERBAIKAN: Validasi input yang lebih spesifik
if (empty($input['email'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Error: Email tidak terkirim saat verifikasi OTP.']);
    exit();
}
if (empty($input['otp_code'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Error: Kode OTP tidak terkirim saat verifikasi.']);
    exit();
}

$email = $input['email'];
$otp_code = $input['otp_code'];

try {
    // 1. Cari user yang statusnya masih PENDING
    $stmt_user = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'PENDING_VERIFICATION'");
    $stmt_user->execute([$email]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        throw new Exception('Email tidak ditemukan atau sudah terverifikasi.');
    }
    $user_id = $user['id'];

    // 2. Cek OTP
    $stmt_otp = $pdo->prepare("SELECT id, expires_at FROM user_otps WHERE user_id = ? AND otp_code = ? ORDER BY id DESC LIMIT 1");
    $stmt_otp->execute([$user_id, $otp_code]);
    $otp = $stmt_otp->fetch(PDO::FETCH_ASSOC);

    if (!$otp || strtotime($otp['expires_at']) < time()) {
        http_response_code(400);
        throw new Exception('Kode OTP salah atau telah kedaluwarsa.');
    }

    $pdo->beginTransaction();

    // 3. Update status user menjadi ACTIVE
    $stmt_activate = $pdo->prepare("UPDATE users SET status = 'ACTIVE' WHERE id = ?");
    $stmt_activate->execute([$user_id]);
    
    // 4. Buat rekening tabungan
    $stmt_account = $pdo->prepare("INSERT INTO accounts (user_id, account_type, status) VALUES (?, 'TABUNGAN', 'ACTIVE')");
    $stmt_account->execute([$user_id]);

    // 5. Hapus OTP
    $stmt_delete_otp = $pdo->prepare("DELETE FROM user_otps WHERE user_id = ?");
    $stmt_delete_otp->execute([$user_id]);
    
    $pdo->commit();
    
    // Kirim notifikasi ke staf
    try {
        $title = "Nasabah Baru Terdaftar";
        $message = "Nasabah baru, " . $user['full_name'] . ", telah berhasil mendaftar melalui pendaftaran online.";
        notify_staff_by_role($pdo, [1, 2, 3, 6], $title, $message);
    } catch (Exception $e) {
        error_log("Gagal kirim notifikasi pendaftaran: " . $e->getMessage());
    }

    // Siapkan data user untuk frontend
    $user_data_for_frontend = [
        'id' => $user['id'],
        'bankId' => $user['bank_id'],
        'roleId' => $user['role_id'],
        'fullName' => $user['full_name'],
        'email' => $user['email']
    ];
    
    $token = create_jwt_token($user_id, $user['role_id']);
    
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Pendaftaran berhasil. Akun Anda telah diaktifkan.',
        'token' => $token,
        'user' => $user_data_for_frontend
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Menggunakan kode HTTP yang sudah ada jika diset (misal 400 atau 404)
    $code = http_response_code() >= 400 ? http_response_code() : 500;
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
