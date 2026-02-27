<?php
// File: app/user_create_withdrawal_request.php
// Penjelasan: Nasabah membuat permintaan penarikan saldo.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/notification_helper.php';

$input = json_decode(file_get_contents('php://input'), true);
$required = ['withdrawal_account_id', 'amount', 'pin'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}

$withdrawal_account_id = $input['withdrawal_account_id'];
$amount = (float)$input['amount'];
$pin = $input['pin'];

try {
    $pdo->beginTransaction();

    // 1. Verifikasi PIN
    $stmt_pin = $pdo->prepare("SELECT pin_hash FROM users WHERE id = ?");
    $stmt_pin->execute([$authenticated_user_id]);
    if (!password_verify($pin, $stmt_pin->fetchColumn())) {
        throw new Exception("PIN Anda salah.");
    }

    // 2. Kunci rekening tabungan dan cek saldo
    $stmt_savings = $pdo->prepare("SELECT id, balance FROM accounts WHERE user_id = ? AND account_type = 'TABUNGAN' FOR UPDATE");
    $stmt_savings->execute([$authenticated_user_id]);
    $savings_account = $stmt_savings->fetch(PDO::FETCH_ASSOC);

    if (!$savings_account || $savings_account['balance'] < $amount) {
        throw new Exception("Saldo tidak mencukupi untuk melakukan penarikan.");
    }

    // 3. Potong saldo nasabah (tahan dana)
    $stmt_debit = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
    $stmt_debit->execute([$amount, $savings_account['id']]);

    // 4. Buat permintaan penarikan
    $stmt_req = $pdo->prepare(
        "INSERT INTO withdrawal_requests (user_id, withdrawal_account_id, amount) VALUES (?, ?, ?)"
    );
    $stmt_req->execute([$authenticated_user_id, $withdrawal_account_id, $amount]);
    
    // 5. Catat transaksi sebagai TARIK_TUNAI (atau jenis baru: PENARIKAN_DANA)
    $desc = "Permintaan penarikan dana ke rekening eksternal";
    $stmt_log = $pdo->prepare(
        "INSERT INTO transactions (from_account_id, transaction_type, amount, description, status) VALUES (?, 'TARIK_TUNAI', ?, ?, 'PENDING')"
    );
    $stmt_log->execute([$savings_account['id'], $amount, $desc]);

    $pdo->commit();
    
    // Kirim notifikasi ke staf
    $stmt_user = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt_user->execute([$authenticated_user_id]);
    $customer_name = $stmt_user->fetchColumn();
    
    $title = "Permintaan Penarikan Baru";
    $message = "Nasabah " . $customer_name . " mengajukan permintaan penarikan sebesar " . number_format($amount, 0, ',', '.');
    notify_staff_by_role($pdo, [1, 2, 3, 5], $title, $message); // Teller, KaUnit, KaCab, Super Admin

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Permintaan penarikan Anda berhasil dikirim dan akan segera diproses.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuat permintaan: ' . $e->getMessage()]);
}
