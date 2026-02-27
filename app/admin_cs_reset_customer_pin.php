<?php
// File: app/admin_cs_reset_customer_pin.php
// Penjelasan: CS mereset PIN transaksi nasabah.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: CS, Kepala Unit ke atas
$allowed_roles = [1, 2, 3, 6];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$customer_id = $input['customer_id'] ?? null;

if (!$customer_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Nasabah wajib diisi.']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Reset PIN di tabel users. Di aplikasi nyata, PIN harus di-hash.
    // Menyetelnya ke NULL memaksa pengguna untuk membuat yang baru saat transaksi berikutnya.
    $stmt = $pdo->prepare("UPDATE users SET pin_hash = NULL WHERE id = ? AND role_id = 9");
    $stmt->execute([$customer_id]);

    if ($stmt->rowCount() > 0) {
        // CATAT DI LOG AUDIT (PERBAIKAN: Menghapus kolom 'target_id')
        $details_json = json_encode(['reset_pin_for_customer_id' => $customer_id]);
        $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'CUSTOMER_PIN_RESET', ?, ?)");
        $stmt_audit->execute([$authenticated_user_id, $details_json, $_SERVER['REMOTE_ADDR']]);

        $pdo->commit();
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'PIN nasabah berhasil direset. Nasabah akan diminta membuat PIN baru pada transaksi berikutnya.']);
    } else {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Nasabah tidak ditemukan.']);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mereset PIN.']);
}
