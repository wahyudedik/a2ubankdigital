<?php
// File: app/bill_payment_execute.php
// REVISI TOTAL: Menyesuaikan dengan struktur payload dari frontend dan menambahkan validasi yang lebih baik.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/digiflazz_helper.php';
require_once __DIR__ . '/helpers/notification_helper.php';
require_once __DIR__ . '/helpers/push_notification_helper.php';
require_once __DIR__ . '/helpers/log_helper.php';

$input = json_decode(file_get_contents('php://input'), true);

// PERBAIKAN: Validasi disesuaikan dengan payload "datar" dari frontend
if (empty($input['pin']) || empty($input['buyer_sku_code']) || empty($input['customer_no'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => "Data tidak lengkap. Harap ulangi proses dari awal."]);
    exit();
}

$pin = $input['pin'];
// PERBAIKAN: Total amount diambil langsung dari input
$total_amount = (float)($input['total'] ?? 0);

try {
    // 1. Verifikasi PIN
    $stmt_pin = $pdo->prepare("SELECT pin_hash FROM users WHERE id = ?");
    $stmt_pin->execute([$authenticated_user_id]);
    $pin_hash = $stmt_pin->fetchColumn();
    if (!$pin_hash || !password_verify($pin, $pin_hash)) {
        throw new Exception("PIN Anda salah.", 401);
    }
    
    $pdo->beginTransaction();

    // 2. Kunci rekening, cek saldo, dan potong dana
    $stmt_account = $pdo->prepare("SELECT id, balance FROM accounts WHERE user_id = ? AND account_type = 'TABUNGAN' FOR UPDATE");
    $stmt_account->execute([$authenticated_user_id]);
    $savings_account = $stmt_account->fetch(PDO::FETCH_ASSOC);

    if (!$savings_account || $savings_account['balance'] < $total_amount) {
        throw new Exception("Saldo tidak mencukupi.", 400);
    }
    
    $stmt_debit = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
    $stmt_debit->execute([$total_amount, $savings_account['id']]);

    // 3. Buat ref_id unik dan catat transaksi LOKAL dengan status PENDING
    $ref_id = "BKT-" . time() . "-" . $authenticated_user_id;
    $description = "Bayar/Beli " . ($input['description'] ?? 'Produk Digital');
    
    $stmt_log = $pdo->prepare(
        "INSERT INTO transactions (from_account_id, transaction_type, amount, fee, description, status, external_ref_id) 
         VALUES (?, 'PEMBAYARAN_TAGIHAN', ?, ?, ?, 'PENDING', ?)"
    );
    $stmt_log->execute([$savings_account['id'], $input['amount'], $input['fee'], $description, $ref_id]);
    $transaction_id = $pdo->lastInsertId();

    // 4. Siapkan dan kirim request ke Digiflazz
    $payload = [
        'buyer_sku_code' => $input['buyer_sku_code'],
        'customer_no'    => $input['customer_no'],
        'ref_id'         => $ref_id,
        // 'commands' tidak diperlukan untuk Topup/Bill Payment API (Buyer)
    ];

    $digiflazz_response = callDigiflazzApi($payload);
    $digiflazz_data = $digiflazz_response['body']['data'] ?? [];
    
    $is_digiflazz_success = $digiflazz_response['http_code'] === 200 && isset($digiflazz_data['status']) && in_array($digiflazz_data['status'], ['Sukses', 'Pending']);
    
    if ($is_digiflazz_success) {
        $pdo->commit();

        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Pembelian atau pembayaran Anda sedang diproses. Status akan diperbarui otomatis.',
            'data' => $digiflazz_data
        ]);

    } else {
        if ($pdo->inTransaction()) $pdo->rollBack();
        
        log_system_event($pdo, 'WARNING', 'Digiflazz Transaction Failed', ['response' => $digiflazz_response['body'], 'request_ref' => $ref_id]);
        
        $error_message = $digiflazz_data['message'] ?? 'Transaksi Gagal di Sisi Provider.';
        
        http_response_code(400); 
        echo json_encode(['status' => 'error', 'message' => $error_message]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $code = is_int($e->getCode()) && $e->getCode() > 0 ? $e->getCode() : 500;
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

