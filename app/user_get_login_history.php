<?php
// File: app/user_get_login_history.php
// Penjelasan: Nasabah melihat riwayat login ke akun mereka.

require_once 'auth_middleware.php';


try {
    $stmt = $pdo->prepare("SELECT ip_address, user_agent, login_at, status FROM login_history WHERE user_id = ? ORDER BY login_at DESC LIMIT 20");
    $stmt->execute([$authenticated_user_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $history]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil riwayat login.']);
}
?>
