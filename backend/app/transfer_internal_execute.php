<?php
// File: app/transfer_internal_execute.php
// Penjelasan: Memperbaiki deskripsi transaksi untuk penerima.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/push_notification_helper.php';

$input = json_decode(file_get_contents('php://input'), true);

// Validasi input (tidak berubah)
$required_fields = ['destination_account_number', 'amount', 'pin'];
foreach ($required_fields as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}
if (!is_numeric($input['amount']) || $input['amount'] <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Jumlah transfer tidak valid.']);
    exit();
}

$destination_account_number = $input['destination_account_number'];
$amount = (float)$input['amount'];
$pin = $input['pin'];
// PERBAIKAN: Deskripsi sekarang opsional dan memiliki nilai default
$description_from_sender = $input['description'] ?? '';
$transfer_fee = 0.00; 

try {
    // 1. Verifikasi PIN pengguna (tidak berubah)
    $stmt_pin = $pdo->prepare("SELECT pin_hash FROM users WHERE id = ?");
    $stmt_pin->execute([$authenticated_user_id]);
    $user_pin_hash = $stmt_pin->fetchColumn();
    if (!$user_pin_hash || !password_verify($pin, $user_pin_hash)) {
        throw new Exception("PIN Anda salah.", 401);
    }

    $pdo->beginTransaction();

    // 2. Kunci baris rekening pengirim dan penerima
    $stmt_from = $pdo->prepare("SELECT id, balance, user_id FROM accounts WHERE user_id = ? AND account_type = 'TABUNGAN' FOR UPDATE");
    $stmt_from->execute([$authenticated_user_id]);
    $from_account = $stmt_from->fetch(PDO::FETCH_ASSOC);

    $stmt_to = $pdo->prepare("SELECT a.id, a.user_id, u.full_name FROM accounts a JOIN users u ON a.user_id = u.id WHERE a.account_number = ? FOR UPDATE");
    $stmt_to->execute([$destination_account_number]);
    $to_account = $stmt_to->fetch(PDO::FETCH_ASSOC);

    if (!$from_account || !$to_account || $to_account['user_id'] == $from_account['user_id']) {
       throw new Exception("Rekening tidak valid.");
    }

    // 3. Cek saldo (tidak berubah)
    if ($from_account['balance'] < ($amount + $transfer_fee)) {
        throw new Exception("Saldo tidak mencukupi.");
    }
    
    // 4. Lakukan update saldo (tidak berubah)
    $stmt_debit = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
    $stmt_debit->execute([($amount + $transfer_fee), $from_account['id']]);

    $stmt_credit = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
    $stmt_credit->execute([$amount, $to_account['id']]);
    
    // --- 5. PERBAIKAN DESKRIPSI & JENIS TRANSAKSI ---
    $stmt_sender_name = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt_sender_name->execute([$authenticated_user_id]);
    $sender_name = $stmt_sender_name->fetchColumn();
    
    // Deskripsi untuk pengirim
    $description_for_sender = !empty($description_from_sender) ? $description_from_sender : "Transfer ke " . $to_account['full_name'];
    // Deskripsi untuk penerima
    $description_for_recipient = "Transfer dari " . $sender_name;

    // Tentukan jenis transaksi
    $transaction_type = (strpos(strtolower($description_from_sender), 'transaksi qr') !== false) ? 'TRANSFER_QR' : 'TRANSFER_INTERNAL';
    
    // --- 6. Catat transaksi ---
    // Deskripsi yang disimpan di DB adalah deskripsi dari sisi pengirim
    $stmt_log = $pdo->prepare(
        "INSERT INTO transactions (from_account_id, to_account_id, transaction_type, amount, fee, description, status)
        VALUES (?, ?, ?, ?, ?, ?, 'SUCCESS')"
    );
    $stmt_log->execute([$from_account['id'], $to_account['id'], $transaction_type, $amount, $transfer_fee, $description_for_sender]);
    $trx_id = $pdo->lastInsertId();

    $pdo->commit();

    // --- 7. PROSES NOTIFIKASI DENGAN DESKRIPSI YANG BENAR ---
    try {
        $recipient_user_id = $to_account['user_id'];
        $title = "Dana Masuk";
        // Notifikasi ke penerima menggunakan deskripsi yang relevan untuknya
        $message = "Anda menerima transfer sebesar " . number_format($amount, 0, ',', '.') . " dari " . $sender_name . ".";
        
        $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $stmt_notify->execute([$recipient_user_id, $title, $message]);

        sendPushNotification($pdo, $recipient_user_id, $title, $message);

    } catch (Exception $e) {
        // Log error notifikasi
        log_system_event($pdo, 'ERROR', 'Notification process failed after transaction', ['transaction_id' => $trx_id, 'error' => $e->getMessage()]);
    }
    // ----------------------------------------------------

    http_response_code(200);
    echo json_encode([
        'status' => 'success', 
        'message' => 'Transfer berhasil.',
        'data' => ['transaction_id' => $trx_id]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $code = is_int($e->getCode()) && $e->getCode() > 0 ? $e->getCode() : 500;
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => 'Transfer gagal: ' . $e->getMessage()]);
}
