<?php
// File: app/admin_cs_close_ticket.php
// Penjelasan: CS menutup tiket yang sudah selesai.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: CS, Kepala Unit ke atas
$allowed_roles = [1, 2, 3, 6];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$ticket_id = $input['ticket_id'] ?? null;
if (!$ticket_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Tiket wajib diisi.']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'CLOSED' WHERE id = ? AND status != 'CLOSED'");
    $stmt->execute([$ticket_id]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Tiket berhasil ditutup.']);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Tiket tidak ditemukan atau sudah ditutup.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menutup tiket.']);
}
?>
