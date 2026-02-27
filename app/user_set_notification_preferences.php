<?php
// File: app/user_set_notification_preferences.php
// Penjelasan: Nasabah mengatur preferensi notifikasi.

require_once 'auth_middleware.php';


$input = json_decode(file_get_contents('php://input'), true);
// Contoh input: {"promotions": true, "transactions": true, "loan_reminders": false}

try {
    $stmt = $pdo->prepare("UPDATE users SET notification_prefs = ? WHERE id = ?");
    $stmt->execute([json_encode($input), $authenticated_user_id]);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Preferensi notifikasi berhasil diperbarui.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan preferensi.']);
}
?>
