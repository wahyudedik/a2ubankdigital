<?php
// File: app/user_get_loyalty_points.php
// Penjelasan: Nasabah melihat total poin dan riwayat loyalitas.

require_once 'auth_middleware.php';



try {
    // Ambil saldo poin
    $stmt_balance = $pdo->prepare("SELECT loyalty_points_balance FROM users WHERE id = ?");
    $stmt_balance->execute([$authenticated_user_id]);
    $balance = $stmt_balance->fetchColumn();

    // Ambil riwayat
    $stmt_history = $pdo->prepare("SELECT points, description, created_at FROM loyalty_points_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
    $stmt_history->execute([$authenticated_user_id]);
    $history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => ['balance' => (int)$balance, 'history' => $history]]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data poin loyalitas.']);
}
?>
