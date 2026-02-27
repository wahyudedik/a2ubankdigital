<?php
// File: app-backend/admin_update_customer_status.php
// Penjelasan: Staf (Kepala Unit ke atas) mengubah status akun nasabah.
// REVISI: Menambahkan logika untuk mengaktifkan kembali rekening saat status DORMANT diubah.

require_once 'auth_middleware.php';

// Hanya Kepala Unit ke atas yang bisa mengubah status
if ($authenticated_user_role_id > 3) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Peran Anda tidak diizinkan.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$customer_id = $input['customer_id'] ?? null;
$new_status = $input['new_status'] ?? '';
$valid_statuses = ['ACTIVE', 'BLOCKED']; // Status yang bisa di-set oleh admin

if (!$customer_id || !in_array($new_status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Input tidak valid.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Update status utama di tabel users
    $stmt_user = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt_user->execute([$new_status, $customer_id]);

    if ($stmt_user->rowCount() === 0) {
        throw new Exception("Nasabah tidak ditemukan atau status tidak berubah.");
    }

    // --- PERBAIKAN UTAMA ADA DI SINI ---
    // 2. Jika status baru adalah ACTIVE, aktifkan juga semua rekening tabungan yang DORMANT
    if ($new_status === 'ACTIVE') {
        $stmt_accounts = $pdo->prepare(
            "UPDATE accounts SET status = 'ACTIVE' WHERE user_id = ? AND account_type = 'TABUNGAN' AND status = 'DORMANT'"
        );
        $stmt_accounts->execute([$customer_id]);
    }
    // --- AKHIR PERBAIKAN ---

    // 3. Catat di log audit
    $audit_details = json_encode(['target_customer_id' => $customer_id, 'new_status' => $new_status]);
    $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'UPDATE_CUSTOMER_STATUS', ?, ?)");
    $stmt_audit->execute([$authenticated_user_id, $audit_details, $_SERVER['REMOTE_ADDR']]);
    
    $pdo->commit();
    
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Status nasabah berhasil diperbarui.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui status: ' . $e->getMessage()]);
}

