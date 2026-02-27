<?php
// File: app/user_get_goal_savings_detail.php
// Penjelasan: Nasabah melihat detail dan progres Tabungan Rencana.

require_once 'auth_middleware.php';

$account_id = $_GET['id'] ?? null;
if (!$account_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Rekening wajib diisi.']);
    exit();
}

try {
    $sql = "
        SELECT 
            a.id, a.account_number, a.balance, a.status,
            gsd.goal_name, gsd.goal_amount, gsd.target_date, gsd.autodebit_day, gsd.autodebit_amount
        FROM accounts a
        JOIN goal_savings_details gsd ON a.id = gsd.account_id
        WHERE a.id = ? AND a.user_id = ? AND a.account_type = 'RENCANA'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$account_id, $authenticated_user_id]);
    $goal_account = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$goal_account) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Tabungan Rencana tidak ditemukan.']);
        exit();
    }
    
    $progress = 0;
    if ((float)$goal_account['goal_amount'] > 0) {
        $progress = round(((float)$goal_account['balance'] / (float)$goal_account['goal_amount']) * 100, 2);
    }
    $goal_account['progress_percentage'] = $progress;

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $goal_account]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data.']);
}
?>
