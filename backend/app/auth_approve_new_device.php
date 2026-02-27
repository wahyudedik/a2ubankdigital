<?php
// File: app/auth_approve_new_device.php
// Penjelasan: Nasabah memverifikasi login dari perangkat baru via link email.

require_once 'config.php';

// Token akan ada di URL, misal: ?token=xyz
$token = $_GET['token'] ?? '';
if (empty($token)) {
    // Tampilkan halaman error HTML
    exit('Token tidak valid.');
}

try {
    // Di sistem nyata, token akan disimpan di tabel terpisah (misal: device_approvals)
    // dengan user_id, device_info, dan expiry_date.
    // Ini adalah simulasi.
    
    // Anggap token valid, update perangkat menjadi trusted.
    // $stmt = $pdo->prepare("UPDATE user_devices SET is_trusted = 1 WHERE approval_token = ?");
    // $stmt->execute([$token]);
    
    echo "<h1>Perangkat Berhasil Disetujui</h1><p>Anda sekarang dapat login dari perangkat baru Anda.</p>";

} catch (Exception $e) {
    echo "<h1>Verifikasi Gagal</h1><p>Terjadi kesalahan. Silakan coba lagi.</p>";
}
?>
