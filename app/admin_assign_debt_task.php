<?php
// File: app/admin_assign_debt_task.php
// Penjelasan: Manajer menugaskan cicilan macet ke debt collector.
// REVISI: Menambahkan 'assigned_by' ke dalam query INSERT.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: Kepala Unit, Kepala Cabang, Super Admin
$allowed_roles = [1, 2, 3];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$required = ['installment_id', 'collector_id'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}

$installment_id = $input['installment_id'];
$collector_id = $input['collector_id'];

try {
    // 1. Validasi collector
    $stmt_coll = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role_id = 8 AND status = 'ACTIVE'");
    $stmt_coll->execute([$collector_id]);
    if (!$stmt_coll->fetch()) {
        throw new Exception("Debt collector tidak valid atau tidak aktif.");
    }

    // 2. Validasi cicilan
    $stmt_inst = $pdo->prepare("SELECT id FROM loan_installments WHERE id = ? AND status = 'OVERDUE'");
    $stmt_inst->execute([$installment_id]);
    if (!$stmt_inst->fetch()) {
        throw new Exception("Cicilan tidak valid atau sudah lunas.");
    }
    
    // 3. Cek apakah sudah ditugaskan
    $stmt_check = $pdo->prepare("SELECT id FROM debt_collection_assignments WHERE loan_installment_id = ? AND status = 'ASSIGNED'");
    $stmt_check->execute([$installment_id]);
    if ($stmt_check->fetch()) {
        throw new Exception("Cicilan ini sudah ditugaskan ke collector lain.");
    }

    // 4. Buat tugas baru
    $stmt_assign = $pdo->prepare(
        "INSERT INTO debt_collection_assignments (loan_installment_id, collector_id, assigned_by) 
         VALUES (?, ?, ?)"
    );
    $stmt_assign->execute([$installment_id, $collector_id, $authenticated_user_id]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Tugas penagihan berhasil diberikan.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memberikan tugas: ' . $e->getMessage()]);
}
