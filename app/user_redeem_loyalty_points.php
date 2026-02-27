<?php
// File: app/user_redeem_loyalty_points.php
// Penjelasan: Nasabah menukarkan poin loyalitas.

require_once 'auth_middleware.php';

$input = json_decode(file_get_contents('php://input'), true);
$reward_id = $input['reward_id'] ?? null;
// Di sistem nyata, akan ada tabel `rewards` dengan harga poin
$mock_reward_cost = 1000; // Asumsi biaya 1000 poin
$mock_reward_name = "Voucher Pulsa Rp 10.000";

if (!$reward_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Hadiah wajib diisi.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Kunci dan cek saldo poin nasabah
    $stmt_user = $pdo->prepare("SELECT loyalty_points_balance FROM users WHERE id = ? FOR UPDATE");
    $stmt_user->execute([$authenticated_user_id]);
    $balance = (int)$stmt_user->fetchColumn();

    if ($balance < $mock_reward_cost) {
        throw new Exception("Poin tidak mencukupi.");
    }

    // 2. Kurangi poin nasabah
    $stmt_debit = $pdo->prepare("UPDATE users SET loyalty_points_balance = loyalty_points_balance - ? WHERE id = ?");
    $stmt_debit->execute([$mock_reward_cost, $authenticated_user_id]);

    // 3. Catat riwayat penukaran
    $stmt_log = $pdo->prepare("INSERT INTO loyalty_points_history (user_id, points, description) VALUES (?, ?, ?)");
    $stmt_log->execute([$authenticated_user_id, -$mock_reward_cost, "Tukar poin: " . $mock_reward_name]);

    $pdo->commit();
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Poin berhasil ditukarkan.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menukarkan poin: ' . $e->getMessage()]);
}
?>
