<?php
// File: app/admin_teller_withdrawal.php
// Penjelasan: Staf (Teller/CS) melakukan transaksi penarikan tunai dari rekening nasabah.
// Revisi: Memastikan kolom `processed_by` diisi dengan ID staf.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/push_notification_helper.php';

// Role yang bisa mengakses: Teller, CS, Kepala Unit, Kepala Cabang, Super Admin
$allowed_roles = [1, 2, 3, 5, 6];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$required = ['account_number', 'amount'];
foreach ($required as $field) {
    if (empty($input[$field]) || !is_numeric($input['amount']) || $input['amount'] <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi dan amount harus valid."]);
        exit();
    }
}

$account_number = $input['account_number'];
$amount = (float)$input['amount'];
$customer_user_id = null; // Inisialisasi

try {
    $pdo->beginTransaction();

    // 1. Cari rekening dan kunci row, cek saldo
    $stmt_acc = $pdo->prepare("SELECT id, user_id, balance FROM accounts WHERE account_number = ? AND status = 'ACTIVE' FOR UPDATE");
    $stmt_acc->execute([$account_number]);
    $account = $stmt_acc->fetch(PDO::FETCH_ASSOC);

    if (!$account) {
        throw new Exception("Rekening tidak ditemukan atau tidak aktif.");
    }
    $customer_user_id = $account['user_id'];

    if ($account['balance'] < $amount) {
        throw new Exception("Saldo tidak mencukupi untuk penarikan.");
    }
    
    // 2. Kurangi saldo
    $stmt_debit = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
    $stmt_debit->execute([$amount, $account['id']]);

    // 3. Catat transaksi
    $description = "Tarik tunai oleh Teller #" . $authenticated_user_id;
    $stmt_log = $pdo->prepare(
        "INSERT INTO transactions (from_account_id, transaction_type, amount, description, status, processed_by) 
         VALUES (?, 'TARIK_TUNAI', ?, ?, 'SUCCESS', ?)"
    );
    $stmt_log->execute([$account['id'], $amount, $description, $authenticated_user_id]);
    $trx_id = $pdo->lastInsertId();
    
    // 4. Buat notifikasi in-app
    $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $title = "Penarikan Tunai Berhasil";
    $message = "Anda telah berhasil melakukan penarikan tunai sebesar Rp " . number_format($amount, 2, ',', '.') . ".";
    $stmt_notify->execute([$customer_user_id, $title, $message]);
    
    $pdo->commit();

    // 5. KIRIM PUSH NOTIFICATION (SETELAH COMMIT)
    try {
        sendPushNotification($pdo, $customer_user_id, $title, $message);
    } catch (Exception $e) {
        error_log("Push notification failed for withdrawal trx_id $trx_id: " . $e->getMessage());
    }

    http_response_code(200);
    echo json_encode([
        'status' => 'success', 
        'message' => 'Penarikan tunai berhasil.',
        'data' => ['transaction_id' => $trx_id]
    ]);

} catch (Exception $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Transaksi gagal: ' . $e->getMessage()]);
}

