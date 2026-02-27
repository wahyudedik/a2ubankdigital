<?php
// File: app/user_create_ticket.php
// Penjelasan: Nasabah membuat tiket keluhan baru.
// REVISI: Menyesuaikan nama kolom 'customer_user_id'.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/notification_helper.php';

$input = json_decode(file_get_contents('php://input'), true);
$required = ['subject', 'description'];
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
         VALUES (?, ?, ?, 'OPEN', NULL)"
    );
    $stmt->execute([$authenticated_user_id, $ticket_code, $input['subject']]);
    $ticket_id = $pdo->lastInsertId();
    
    // Simpan pesan pertama sebagai balasan
    $stmt_reply = $pdo->prepare("INSERT INTO support_ticket_replies (ticket_id, user_id, message) VALUES (?, ?, ?)");
    $stmt_reply->execute([$ticket_id, $authenticated_user_id, $input['description']]);

    $pdo->commit();

    // Notifikasi ke staf
    $user_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $user_stmt->execute([$authenticated_user_id]);
    $customer_name = $user_stmt->fetchColumn();

    $title = "Tiket Baru Diterima";
    $message = "Nasabah " . $customer_name . " telah membuat tiket baru: '" . $input['subject'] . "'";
    // Notifikasi ke CS (6) dan atasan di unit terkait
    notify_staff_hierarchically($pdo, $authenticated_user_id, 6, $title, $message);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Tiket Anda telah kami terima dan akan segera diproses.', 'data' => ['ticket_id' => $ticket_id]]);

} catch (PDOException $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuat tiket.']);
}
