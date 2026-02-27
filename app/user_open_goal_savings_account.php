<?php
// File: app/user_open_goal_savings_account.php
// Penjelasan: Nasabah membuka rekening baru untuk Tabungan Rencana.

require_once 'auth_middleware.php';



$input = json_decode(file_get_contents('php://input'), true);
// Validasi input untuk goal_name, goal_amount, dll.
// ...

try {
    $pdo->beginTransaction();

    // 1. Buat rekening baru di tabel accounts
    $stmt_acc = $pdo->prepare("INSERT INTO accounts (user_id, account_type, status) VALUES (?, 'RENCANA', 'ACTIVE')");
    $stmt_acc->execute([$authenticated_user_id]);
    $new_account_id = $pdo->lastInsertId();

    // 2. Simpan detail tujuan di tabel goal_savings_details
    $stmt_goal = $pdo->prepare("
        INSERT INTO goal_savings_details (account_id, goal_name, goal_amount, target_date, autodebit_day, autodebit_amount)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt_goal->execute([
        $new_account_id,
        $input['goal_name'],
        $input['goal_amount'],
        $input['target_date'],
        $input['autodebit_day'],
        $input['autodebit_amount']
    ]);

    $pdo->commit();
    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Tabungan Rencana berhasil dibuat.', 'data' => ['new_account_id' => $new_account_id]]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuat Tabungan Rencana: ' . $e->getMessage()]);
}
?>
