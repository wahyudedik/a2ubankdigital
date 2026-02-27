<?php
// File: app/admin_process_account_closure.php
// Penjelasan: Admin memproses permintaan penutupan akun.
// REVISI: Menambahkan pencatatan ke log audit.

require_once 'auth_middleware.php';

$allowed_roles = [1, 2, 3];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$request_id = $input['request_id'] ?? null;
$action = $input['action'] ?? ''; // 'APPROVE' atau 'REJECT'

if (!$request_id || !in_array($action, ['APPROVE', 'REJECT'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Input tidak valid.']);
    exit();
}

try {
    $pdo->beginTransaction();

    $new_status = ($action === 'APPROVE') ? 'APPROVED' : 'REJECTED';
    
    // Ambil user_id dari request sebelum update
    $stmt_user = $pdo->prepare("SELECT user_id FROM account_closure_requests WHERE id = ? AND status = 'PENDING'");
    $stmt_user->execute([$request_id]);
    $user_id_to_process = $stmt_user->fetchColumn();

    if (!$user_id_to_process) {
        throw new Exception("Permintaan tidak ditemukan atau sudah diproses.");
    }
    
    $stmt = $pdo->prepare("UPDATE account_closure_requests SET status = ?, processed_by = ? WHERE id = ?");
    $stmt->execute([$new_status, $authenticated_user_id, $request_id]);

    if ($action === 'APPROVE') {
        $pdo->prepare("UPDATE users SET status = 'CLOSED' WHERE id = ?")->execute([$user_id_to_process]);
        $pdo->prepare("UPDATE accounts SET status = 'CLOSED' WHERE user_id = ?")->execute([$user_id_to_process]);
    }

    // --- PENAMBAHAN LOG AUDIT ---
    $audit_details = json_encode(['request_id' => $request_id, 'customer_id' => $user_id_to_process, 'action' => $action]);
    $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'PROCESS_ACCOUNT_CLOSURE', ?, ?)");
    $stmt_audit->execute([$authenticated_user_id, $audit_details, $_SERVER['REMOTE_ADDR']]);
    // --- AKHIR PENAMBAHAN ---

    $pdo->commit();
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => "Permohonan berhasil di-" . strtolower($new_status) . "."]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memproses permintaan: ' . $e->getMessage()]);
}
?>
