<?php
// File: app/deposit_account_create.php
// Penjelasan: Nasabah membuka rekening deposito baru dari rekening tabungan.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/notification_helper.php';
require_once __DIR__ . '/helpers/push_notification_helper.php';

$input = json_decode(file_get_contents('php://input'), true);
$required = ['product_id', 'amount'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}

$product_id = $input['product_id'];
$amount = (float)$input['amount'];

try {
    $pdo->beginTransaction();

    // 1. Validasi produk dan jumlah penempatan
    $stmt_prod = $pdo->prepare("SELECT * FROM deposit_products WHERE id = ? AND is_active = 1");
    $stmt_prod->execute([$product_id]);
    $product = $stmt_prod->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception("Produk deposito tidak valid atau tidak aktif.");
    }
    if ($amount < $product['min_amount']) {
        throw new Exception("Jumlah penempatan di bawah minimum yang disyaratkan produk.");
    }

    // 2. Kunci rekening tabungan, cek saldo, dan potong dana
    $stmt_savings = $pdo->prepare("SELECT id, balance FROM accounts WHERE user_id = ? AND account_type = 'TABUNGAN' FOR UPDATE");
    $stmt_savings->execute([$authenticated_user_id]);
    $savings_account = $stmt_savings->fetch(PDO::FETCH_ASSOC);

    if (!$savings_account || $savings_account['balance'] < $amount) {
        throw new Exception("Saldo tabungan tidak mencukupi untuk penempatan deposito.");
    }
    
    $stmt_debit = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
    $stmt_debit->execute([$amount, $savings_account['id']]);

    // 3. Hitung tanggal jatuh tempo
    $tenor_months = (int)$product['tenor_months'];
    $maturity_date = date('Y-m-d', strtotime("+$tenor_months months"));

    // 4. Buat rekening deposito baru
    $stmt_create = $pdo->prepare(
        "INSERT INTO accounts (user_id, account_type, balance, status, deposit_product_id, maturity_date) 
         VALUES (?, 'DEPOSITO', ?, 'ACTIVE', ?, ?)"
    );
    $stmt_create->execute([$authenticated_user_id, $amount, $product_id, $maturity_date]);
    $new_deposit_id = $pdo->lastInsertId();

    // 5. Catat transaksi
    $description = "Pembukaan " . $product['product_name'];
    $stmt_log = $pdo->prepare(
        "INSERT INTO transactions (from_account_id, to_account_id, transaction_type, amount, description, status) 
         VALUES (?, ?, 'PEMBUKAAN_DEPOSITO', ?, ?, 'SUCCESS')"
    );
    $stmt_log->execute([$savings_account['id'], $new_deposit_id, $amount, $description]);

    $pdo->commit();
    
    // --- Kirim Notifikasi setelah proses berhasil ---
    try {
        // a. Notifikasi untuk Nasabah
        $title_customer = "Pembukaan Deposito Berhasil";
        $message_customer = "Anda telah berhasil membuka " . $product['product_name'] . " sebesar " . number_format($amount, 0, ',', '.') . " yang akan jatuh tempo pada " . $maturity_date . ".";
        
        $stmt_notify_customer = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $stmt_notify_customer->execute([$authenticated_user_id, $title_customer, $message_customer]);
        sendPushNotification($pdo, $authenticated_user_id, $title_customer, $message_customer);

        // b. Notifikasi untuk Staf
        $stmt_user = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt_user->execute([$authenticated_user_id]);
        $customer_name = $stmt_user->fetchColumn();
        
        $title_staff = "Penempatan Deposito Baru";
        $message_staff = "Nasabah " . $customer_name . " telah menempatkan deposito baru (" . $product['product_name'] . ") sebesar " . number_format($amount, 0, ',', '.');
        notify_staff_by_role($pdo, [1, 2, 3, 6], $title_staff, $message_staff); // CS, KaUnit, KaCab, Super Admin

    } catch (Exception $e) {
        error_log("Gagal mengirim notifikasi pembukaan deposito: " . $e->getMessage());
    }
    // ----------------------------------------------------

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Pembukaan deposito berhasil.']);

} catch (Exception $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuka deposito: ' . $e->getMessage()]);
}
