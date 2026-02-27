<?php
// File: app/user_get_tickets.php
// Penjelasan: Nasabah melihat daftar tiket keluhan miliknya.

require_once 'auth_middleware.php';

try {
    $stmt = $pdo->prepare("
        SELECT id, category, subject, status, created_at, updated_at
        FROM support_tickets
        WHERE customer_user_id = ?
        ORDER BY updated_at DESC
    ");
    $stmt->execute([$authenticated_user_id]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $tickets]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data tiket.']);
}
?>
