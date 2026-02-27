<?php
// File: app/admin_update_staff_status.php
// Penjelasan: Mengubah status akun staf (aktif/nonaktif).
// REVISI: Menambahkan pencatatan ke log audit.

require_once 'auth_middleware.php';

$allowed_roles = [1, 2];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$staff_id = $input['staff_id'] ?? null;
$new_status = $input['new_status'] ?? '';

if (!$staff_id || !in_array($new_status, ['ACTIVE', 'INACTIVE'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Input tidak valid.']);
    exit();
}

if ($staff_id == $authenticated_user_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak dapat mengubah status akun Anda sendiri.']);
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt_check = $pdo->prepare("SELECT role_id FROM users WHERE id = ?");
    $stmt_check->execute([$staff_id]);
    $target_user_role = $stmt_check->fetchColumn();

    if (!$target_user_role || $target_user_role <= $authenticated_user_role_id) {
        http_response_code(403);
        throw new Exception('Akses ditolak: Anda tidak dapat mengubah status admin dengan level yang sama atau lebih tinggi.');
    }

    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role_id != 9");
    $stmt->execute([$new_status, $staff_id]);
    
    if ($stmt->rowCount() > 0) {
        // --- PENAMBAHAN LOG AUDIT ---
        $audit_details = json_encode(['target_staff_id' => $staff_id, 'new_status' => $new_status]);
        $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'UPDATE_STAFF_STATUS', ?, ?)");
        $stmt_audit->execute([$authenticated_user_id, $audit_details, $_SERVER['REMOTE_ADDR']]);
        // --- AKHIR PENAMBAHAN ---

        $pdo->commit();
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Status staf berhasil diperbarui.']);
    } else {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui status staf.']);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui status: ' . $e->getMessage()]);
}
?>
