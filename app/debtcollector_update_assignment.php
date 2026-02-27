<?php
// File: app/debtcollector_update_assignment.php
// Penjelasan: Debt Collector memperbarui status tugas penagihan.

require_once 'auth_middleware.php';

// Hanya Debt Collector
if ($authenticated_user_role_id !== 7) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$assignment_id = $input['assignment_id'] ?? null;
$new_status = $input['new_status'] ?? ''; // RESOLVED, FAILED
$notes = $input['notes'] ?? '';

if (!$assignment_id || !in_array($new_status, ['RESOLVED', 'FAILED']) || empty($notes)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Input tidak valid.']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE debt_assignments SET status = ?, notes = ? WHERE id = ? AND collector_user_id = ? AND status = 'ASSIGNED'");
    $stmt->execute([$new_status, $notes, $assignment_id, $authenticated_user_id]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Tugas penagihan berhasil diperbarui.']);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Tugas tidak ditemukan atau sudah diproses.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui tugas.']);
}
?>
