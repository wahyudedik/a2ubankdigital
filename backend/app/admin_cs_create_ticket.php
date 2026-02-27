<?php
// File: app/admin_cs_create_ticket.php
// Penjelasan: CS membuat tiket keluhan/permintaan dari nasabah.
// REVISI: Menyesuaikan nama kolom 'customer_user_id'.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: CS, Kepala Unit ke atas
$allowed_roles = [1, 2, 3, 6];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$required = ['customer_id', 'subject', 'description'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}

try {
    $pdo->beginTransaction();
    $ticket_code = 'TCK-' . time();
    $stmt = $pdo->prepare(
        "INSERT INTO support_tickets (customer_user_id, ticket_code, subject, status, created_by_staff_id) 
         VALUES (?, ?, ?, 'OPEN', ?)"
    );
    $stmt->execute([$input['customer_id'], $ticket_code, $input['subject'], $authenticated_user_id]);
    $ticket_id = $pdo->lastInsertId();

    // Simpan deskripsi sebagai balasan pertama dari staf
    $stmt_reply = $pdo->prepare("INSERT INTO support_ticket_replies (ticket_id, user_id, message) VALUES (?, ?, ?)");
    $stmt_reply->execute([$ticket_id, $authenticated_user_id, $input['description']]);
    
    $pdo->commit();

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Tiket berhasil dibuat.', 'data' => ['ticket_id' => $ticket_id]]);

} catch (PDOException $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuat tiket.']);
}
