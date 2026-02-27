<?php
// File: app/admin_edit_staff.php
// Penjelasan: Endpoint baru untuk memperbarui data staf oleh admin yang berwenang.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/hierarchy_helper.php';

// Hanya Super Admin dan Kepala Cabang yang bisa
$allowed_roles = [1, 2];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$staff_id = $input['staff_id'] ?? null;

if (!$staff_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Staf wajib diisi.']);
    exit();
}

try {
    // --- Validasi Keamanan & Hierarki ---
    $stmt_check = $pdo->prepare("SELECT role_id, unit_id FROM users WHERE id = ?");
    $stmt_check->execute([$staff_id]);
    $target_staff = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$target_staff) {
        http_response_code(404);
        throw new Exception('Staf tidak ditemukan.');
    }

    // 1. Admin tidak bisa mengedit user dengan level yang sama atau lebih tinggi
    if ($target_staff['role_id'] <= $authenticated_user_role_id) {
        http_response_code(403);
        throw new Exception('Akses ditolak: Anda tidak dapat mengubah data admin dengan level yang sama atau lebih tinggi.');
    }

    // 2. Jika bukan Super Admin, pastikan target staf berada dalam lingkup unitnya
    if ($authenticated_user_role_id !== 1) {
        if (!in_array($target_staff['unit_id'], $accessible_unit_ids)) {
             http_response_code(403);
             throw new Exception('Akses ditolak: Staf ini tidak berada di dalam lingkup unit Anda.');
        }
    }
    // --- Akhir Validasi ---

    $pdo->beginTransaction();

    $stmt_update = $pdo->prepare(
        "UPDATE users SET full_name = ?, email = ?, role_id = ? WHERE id = ?"
    );
    $stmt_update->execute([$input['full_name'], $input['email'], $input['role_id'], $staff_id]);

    // Catat di log audit
    $details = json_encode(['edited_staff_id' => $staff_id, 'new_name' => $input['full_name'], 'new_role_id' => $input['role_id']]);
    $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'EDIT_STAFF', ?, ?)");
    $stmt_audit->execute([$authenticated_user_id, $details, $_SERVER['REMOTE_ADDR']]);

    $pdo->commit();

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Data staf berhasil diperbarui.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $code = $e->getCode() === 403 ? 403 : 500;
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
