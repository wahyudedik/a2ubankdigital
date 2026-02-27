<?php
// File: app/admin_manual_transaction_reversal.php
// Penjelasan: Super Admin membatalkan transaksi. Sangat berisiko.

require_once 'auth_middleware.php';

// HANYA Super Admin
if ($authenticated_user_role_id !== 1) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$transaction_id = $input['transaction_id'] ?? null;
$reason = $input['reason'] ?? '';

if (!$transaction_id || empty($reason)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Transaksi dan alasan wajib diisi.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Ambil detail transaksi dan kunci row
    $stmt_trx = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND status = 'SUCCESS' FOR UPDATE");
    $stmt_trx->execute([$transaction_id]);
    $trx = $stmt_trx->fetch(PDO::FETCH_ASSOC);

    if (!$trx) {
        throw new Exception("Transaksi tidak ditemukan atau sudah dibatalkan.");
    }
    
    $amount = (float)$trx['amount'];

    // 2. Lakukan pembalikan saldo
    if ($trx['to_account_id']) {
        $stmt_debit = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
        $stmt_debit->execute([$amount, $trx['to_account_id']]);
    }
    if ($trx['from_account_id']) {
        $stmt_credit = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
        $stmt_credit->execute([$amount, $trx['from_account_id']]);
    }

    // 3. Ubah status transaksi asli
    $stmt_update_trx = $pdo->prepare("UPDATE transactions SET status = 'REVERSED' WHERE id = ?");
    $stmt_update_trx->execute([$transaction_id]);

    // 4. Catat transaksi reversal baru
    $desc = "Reversal untuk Trx ID: " . $transaction_id . ". Alasan: " . $reason;
    $stmt_log_rev = $pdo->prepare("INSERT INTO transactions (from_account_id, to_account_id, transaction_type, amount, description, status) VALUES (?, ?, 'REVERSAL', ?, ?, 'SUCCESS')");
    $stmt_log_rev->execute([$trx['to_account_id'], $trx['from_account_id'], $amount, $desc]);
    
    // 5. CATAT DI LOG AUDIT (PERBAIKAN: Menghapus kolom 'target_id')
    $details_json = json_encode(['reversed_transaction_id' => $transaction_id, 'reason' => $reason]);
    $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'TRANSACTION_REVERSAL', ?, ?)");
    $stmt_audit->execute([$authenticated_user_id, $details_json, $_SERVER['REMOTE_ADDR']]);

    $pdo->commit();

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Transaksi berhasil dibatalkan.']);

} catch (Exception $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal membatalkan transaksi: ' . $e->getMessage()]);
}
