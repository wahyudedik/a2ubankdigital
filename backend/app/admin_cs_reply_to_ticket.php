<?php
// File: app/admin_cs_reply_to_ticket.php
// Penjelasan: CS menambahkan balasan ke sebuah tiket.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: CS, Kepala Unit ke atas
$allowed_roles = [1, 2, 3, 6];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$required = ['ticket_id', 'message'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}

try {
    $pdo->beginTransaction();

    // 1. Masukkan balasan baru
    $stmt_reply = $pdo->prepare("INSERT INTO support_ticket_replies (ticket_id, user_id, message) VALUES (?, ?, ?)");
    $stmt_reply->execute([$input['ticket_id'], $authenticated_user_id, $input['message']]);

    // 2. Update status tiket menjadi "IN_PROGRESS" jika masih "OPEN"
    $stmt_update = $pdo->prepare("UPDATE support_tickets SET status = 'IN_PROGRESS' WHERE id = ? AND status = 'OPEN'");
    $stmt_update->execute([$input['ticket_id']]);

    // Di aplikasi nyata, kirim notifikasi email/push ke nasabah bahwa tiketnya telah dibalas.

    $pdo->commit();

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Balasan berhasil dikirim.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim balasan.']);
}
?>
