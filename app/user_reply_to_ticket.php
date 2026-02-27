<?php
// File: app/user_reply_to_ticket.php
// Penjelasan: Nasabah menambahkan balasan ke tiket miliknya.

require_once 'auth_middleware.php';

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
    // Validasi kepemilikan tiket
    $stmt_check = $pdo->prepare("SELECT id FROM support_tickets WHERE id = ? AND customer_user_id = ?");
    $stmt_check->execute([$input['ticket_id'], $authenticated_user_id]);
    if (!$stmt_check->fetch()) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki akses ke tiket ini.']);
        exit();
    }
    
    // Masukkan balasan
    $stmt_reply = $pdo->prepare("INSERT INTO support_ticket_replies (ticket_id, user_id, message) VALUES (?, ?, ?)");
    $stmt_reply->execute([$input['ticket_id'], $authenticated_user_id, $input['message']]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Balasan berhasil dikirim.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim balasan.']);
}
?>
