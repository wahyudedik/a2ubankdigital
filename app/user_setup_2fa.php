<?php
// File: app/user_setup_2fa.php
// Penjelasan: Nasabah mengaktifkan Otentikasi Dua Faktor (2FA).

require_once 'auth_middleware.php';
require_once 'vendor/autoload.php';

use OTPHP\TOTP;

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? ''; // 'generate_secret' atau 'enable'

try {
    if ($action === 'generate_secret') {
        // Buat secret baru untuk user
        $otp = TOTP::create();
        $secret = $otp->getSecret();
        
        // Simpan secret sementara di sesi atau tabel terpisah (lebih aman)
        // Di sini kita update langsung ke user tapi belum diaktifkan
        $stmt = $pdo->prepare("UPDATE users SET totp_secret = ? WHERE id = ?");
        $stmt->execute([$secret, $authenticated_user_id]);
        
        // Buat URI untuk QR code
        $otp->setLabel($authenticated_user_email);
        $otp->setIssuer('Bank Taskora');
        $qr_code_uri = $otp->getProvisioningUri();

        http_response_code(200);
        echo json_encode(['status' => 'success', 'data' => ['secret' => $secret, 'qr_code_uri' => $qr_code_uri]]);

    } elseif ($action === 'enable') {
        $code = $input['code'] ?? '';
        if (empty($code)) throw new Exception("Kode 2FA wajib diisi.");

        // Ambil secret dari DB
        $stmt = $pdo->prepare("SELECT totp_secret FROM users WHERE id = ?");
        $stmt->execute([$authenticated_user_id]);
        $secret = $stmt->fetchColumn();
        if (!$secret) throw new Exception("Secret tidak ditemukan. Mohon ulangi proses.");

        $otp = TOTP::createFromSecret($secret);
        if ($otp->verify($code)) {
            // Aktifkan 2FA
            $stmt_enable = $pdo->prepare("UPDATE users SET is_totp_enabled = 1 WHERE id = ?");
            $stmt_enable->execute([$authenticated_user_id]);
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => '2FA berhasil diaktifkan.']);
        } else {
            throw new Exception("Kode 2FA tidak valid.");
        }
    } else {
        throw new Exception("Aksi tidak valid.");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
