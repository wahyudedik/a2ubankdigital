<?php
// File: app/user_external_transfer_execute.php
// Penjelasan: Nasabah mengeksekusi transfer ke bank lain.
// REVISI: Mengambil biaya transfer dari database.

require_once 'auth_middleware.php';

$input = json_decode(file_get_contents('php://input'), true);
// Validasi input: bank_code, account_number, amount, pin, etc.
// ...
$amount = (float)($input['amount'] ?? 0);

try {
    // --- MENGAMBIL BIAYA TRANSFER DARI DATABASE ---
    $stmt_fee = $pdo->prepare("SELECT config_value FROM system_configurations WHERE config_key = 'TRANSFER_FEE_EXTERNAL'");
    $stmt_fee->execute();
    $admin_fee = (float)$stmt_fee->fetchColumn();
    // ----------------------------------------------
    
    $total_debit = $amount + $admin_fee;

    $pdo->beginTransaction();

    // 1. Verifikasi PIN & Saldo
    // ... (Logika sama seperti transfer internal)
    $stmt_from = $pdo->prepare("SELECT id, balance FROM accounts WHERE user_id = ? AND account_type = 'TABUNGAN' AND status = 'ACTIVE' FOR UPDATE");
    $stmt_from->execute([$authenticated_user_id]);
    $from_account = $stmt_from->fetch(PDO::FETCH_ASSOC);
    if (!$from_account || $from_account['balance'] < $total_debit) {
        throw new Exception("Saldo tidak mencukupi.");
    }

    // 2. Debit rekening nasabah
    $stmt_debit = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
    $stmt_debit->execute([$total_debit, $from_account['id']]);

    // 3. Catat transaksi
    $desc = "Transfer ke Bank " . $input['bank_code'] . " a/n " . $input['recipient_name'];
    $stmt_log = $pdo->prepare("INSERT INTO transactions (from_account_id, transaction_type, amount, description, status, fee) VALUES (?, 'TRANSFER_EKSTERNAL', ?, ?, 'PENDING_SETTLEMENT', ?)");
    $stmt_log->execute([$from_account['id'], $amount, $desc, $admin_fee]);
    
    // Di dunia nyata, di sini akan ada proses antrian untuk settlement ke API switching.
    
    $pdo->commit();
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Transfer sedang diproses. Anda akan menerima notifikasi jika sudah berhasil.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal melakukan transfer: ' . $e->getMessage()]);
}
?>
