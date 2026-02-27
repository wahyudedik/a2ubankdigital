<?php
// File: app/digital_products_purchase.php
// Penjelasan: Memproses transaksi pembelian produk digital (misal: pulsa).
// REVISI: Menambahkan pemicu push notification.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/push_notification_helper.php';

$input = json_decode(file_get_contents('php://input'), true);

$required = ['product_code', 'customer_number', 'pin'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}

$product_code = $input['product_code'];
$customer_number = $input['customer_number'];
$pin = $input['pin'];

try {
    // 1. Verifikasi PIN
    $stmt_pin = $pdo->prepare("SELECT pin_hash FROM users WHERE id = ?");
    $stmt_pin->execute([$authenticated_user_id]);
    if (!password_verify($pin, $stmt_pin->fetchColumn())) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'PIN Anda salah.']);
        exit();
    }

    $pdo->beginTransaction();

    // 2. Dapatkan detail produk dan harga
    $stmt_product = $pdo->prepare("SELECT price, product_name FROM digital_products WHERE product_code = ? AND is_active = 1");
    $stmt_product->execute([$product_code]);
    $product = $stmt_product->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception("Produk tidak ditemukan atau tidak aktif.");
    }
    $amount = (float)$product['price'];

    // 3. Kunci rekening, cek saldo, dan kurangi
    $stmt_account = $pdo->prepare("SELECT id, balance FROM accounts WHERE user_id = ? AND account_type = 'TABUNGAN' FOR UPDATE");
    $stmt_account->execute([$authenticated_user_id]);
    $account = $stmt_account->fetch(PDO::FETCH_ASSOC);

    if ($account['balance'] < $amount) {
        throw new Exception("Saldo tidak mencukupi.");
    }

    $stmt_debit = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
    $stmt_debit->execute([$amount, $account['id']]);

    // 4. Catat transaksi
    $description = "Pembelian {$product['product_name']} ke {$customer_number}";
    $stmt_log = $pdo->prepare("INSERT INTO transactions (from_account_id, transaction_type, amount, fee, description, status) VALUES (?, 'PEMBELIAN_PRODUK', ?, 0, ?, 'SUCCESS')");
    $stmt_log->execute([$account['id'], $amount, $description]);
    $trx_id = $pdo->lastInsertId();

    // Di dunia nyata: panggil API ke aggregator pulsa/PPOB di sini

    $pdo->commit();
    
    // --- 5. KIRIM PUSH NOTIFICATION ---
    try {
        $title = "Pembelian Berhasil";
        $message = "Pembelian {$product['product_name']} sebesar " . number_format($amount, 0, ',', '.') . " berhasil.";
        
        // Notifikasi In-App
        $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $stmt_notify->execute([$authenticated_user_id, $title, $message]);
        
        // Push Notification
        sendPushNotification($pdo, $authenticated_user_id, $title, $message);
    } catch (Exception $e) {
        error_log("Push notification failed for digital purchase trx_id $trx_id: " . $e->getMessage());
    }
    // --- AKHIR PUSH NOTIFICATION ---

    // Di dunia nyata: token PLN akan didapat dari respons API aggregator
    $token_pln = (strpos(strtoupper($product_code), 'PLN') !== false) ? '1234-5678-9012-3456-7890' : null;

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Pembelian berhasil.',
        'data' => [
            'transaction_id' => $trx_id,
            'description' => $description,
            'token' => $token_pln // Kirim token jika ada
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Transaksi gagal: ' . $e->getMessage()]);
}
?>
