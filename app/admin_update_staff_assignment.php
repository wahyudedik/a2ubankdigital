<?php
// File: app/admin_update_staff_assignment.php
// Penjelasan: Endpoint untuk mengubah penugasan unit/cabang seorang staf.
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
$unit_id = $input['unit_id'] ?? null;

if (!$staff_id || !$unit_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Staf dan ID Unit/Cabang wajib diisi.']);
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt_check = $pdo->prepare("SELECT role_id FROM users WHERE id = ?");
    $stmt_check->execute([$staff_id]);
    $target_user_role = $stmt_check->fetchColumn();

    if ($target_user_role && $target_user_role <= $authenticated_user_role_id) {
         http_response_code(403);
         throw new Exception('Akses ditolak: Anda tidak dapat mengubah data admin dengan level yang sama atau lebih tinggi.');
    }

    $stmt_update = $pdo->prepare("UPDATE users SET unit_id = ? WHERE id = ?");
    $stmt_update->execute([$unit_id, $staff_id]);

    if ($stmt_update->rowCount() > 0) {
        // --- PENAMBAHAN LOG AUDIT ---
        $audit_details = json_encode(['target_staff_id' => $staff_id, 'new_unit_id' => $unit_id]);
        $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'UPDATE_STAFF_ASSIGNMENT', ?, ?)");
        $stmt_audit->execute([$authenticated_user_id, $audit_details, $_SERVER['REMOTE_ADDR']]);
        // --- AKHIR PENAMBAHAN ---
        
        $pdo->commit();
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Penugasan staf berhasil diperbarui.']);
    } else {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Staf tidak ditemukan atau tidak ada perubahan data.']);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui penugasan: ' . $e->getMessage()]);
}
