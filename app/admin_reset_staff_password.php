<?php
// File: app/admin_reset_staff_password.php
// Penjelasan: Endpoint baru untuk mereset password staf.

require_once 'auth_middleware.php';

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
    // --- Validasi Keamanan & Hierarki (Sama seperti edit staff) ---
    $stmt_check = $pdo->prepare("SELECT role_id, unit_id FROM users WHERE id = ?");
    $stmt_check->execute([$staff_id]);
    $target_staff = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$target_staff) {
        http_response_code(404);
        throw new Exception('Staf tidak ditemukan.');
    }
    if ($target_staff['role_id'] <= $authenticated_user_role_id) {
        http_response_code(403);
        throw new Exception('Akses ditolak: Anda tidak dapat mereset password admin dengan level yang sama atau lebih tinggi.');
    }
    if ($authenticated_user_role_id !== 1 && !in_array($target_staff['unit_id'], $accessible_unit_ids)) {
        http_response_code(403);
        throw new Exception('Akses ditolak: Staf ini tidak berada di dalam lingkup unit Anda.');
    }
    // --- Akhir Validasi ---

    $pdo->beginTransaction();

    $temp_password = bin2hex(random_bytes(6)); // 12 karakter
    $password_hash = password_hash($temp_password, PASSWORD_BCRYPT);

    $stmt_update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt_update->execute([$password_hash, $staff_id]);

    // Catat di log audit
    $details = json_encode(['reset_password_for_staff_id' => $staff_id]);
    $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'RESET_STAFF_PASSWORD', ?, ?)");
    $stmt_audit->execute([$authenticated_user_id, $details, $_SERVER['REMOTE_ADDR']]);
    
    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'status' => 'success', 
        'message' => 'Password staf berhasil direset.',
        'data' => [
            'temporary_password' => $temp_password
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
