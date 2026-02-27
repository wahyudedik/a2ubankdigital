<?php
// File: app/ewallet_topup_execute.php
// Penjelasan: Memproses transaksi top up e-wallet.
// REVISI: Mengambil biaya admin dari database dan menambahkan push notification.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/push_notification_helper.php';

$input = json_decode(file_get_contents('php://input'), true);
$required = ['inquiry_id', 'amount', 'pin'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}

$inquiry_id = $input['inquiry_id'];
$amount = (float)$input['amount'];
$pin = $input['pin'];

try {
    // --- MENGAMBIL BIAYA ADMIN DARI DATABASE ---
    $stmt_fee = $pdo->prepare("SELECT config_value FROM system_configurations WHERE config_key = 'EWALLET_TOPUP_FEE'");
    $stmt_fee->execute();
    $admin_fee = (float)$stmt_fee->fetchColumn();
    // ---------------------------------------------

    $total_amount = $amount + $admin_fee;

    // 1. Verifikasi PIN
    $stmt_pin = $pdo->prepare("SELECT pin_hash FROM users WHERE id = ?");
    $stmt_pin->execute([$authenticated_user_id]);
    if (!password_verify($pin, $stmt_pin->fetchColumn())) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'PIN Anda salah.']);
        exit();
    }

    $pdo->beginTransaction();

    // 2. Kunci rekening, cek saldo, kurangi
    $stmt_account = $pdo->prepare("SELECT id, balance FROM accounts WHERE user_id = ? AND account_type = 'TABUNGAN' FOR UPDATE");
    $stmt_account->execute([$authenticated_user_id]);
    $account = $stmt_account->fetch(PDO::FETCH_ASSOC);

    if ($account['balance'] < $total_amount) {
        throw new Exception("Saldo tidak mencukupi.");
    }

    $stmt_debit = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
    $stmt_debit->execute([$total_amount, $account['id']]);

    // 3. Catat transaksi
    $description = "Top Up {$input['biller_code']} ke {$input['phone_number']}";
    $stmt_log = $pdo->prepare("INSERT INTO transactions (from_account_id, transaction_type, amount, fee, description, status) VALUES (?, 'TOPUP_EWALLET', ?, ?, ?, 'SUCCESS')");
    $stmt_log->execute([$account['id'], $amount, $admin_fee, $description]);
    $trx_id = $pdo->lastInsertId();
    
    $pdo->commit();
    
    // --- 4. KIRIM PUSH NOTIFICATION ---
    try {
        $title = "Top Up Berhasil";
        $message = "Top up {$input['biller_code']} sebesar " . number_format($amount, 0, ',', '.') . " berhasil.";
        
        $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $stmt_notify->execute([$authenticated_user_id, $title, $message]);
        
        sendPushNotification($pdo, $authenticated_user_id, $title, $message);
    } catch (Exception $e) {
        error_log("Push notification failed for e-wallet topup trx_id $trx_id: " . $e->getMessage());
    }
    // --- AKHIR PUSH NOTIFICATION ---

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Top up E-Wallet berhasil.',
        'data' => ['transaction_id' => $trx_id]
    ]);

} catch (Exception $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Top up gagal: ' . $e->getMessage()]);
}
?>
