<?php
// File: app/crons/deduct_monthly_fees.php
// Penjelasan: Cron job untuk memotong biaya administrasi bulanan.

if (php_sapi_name() !== 'cli') {
    die("Akses ditolak.");
}
require_once __DIR__ . '/../config.php';

echo "Memulai proses pemotongan biaya administrasi...\n";

try {
    $pdo->beginTransaction();

    // Ambil nilai biaya admin dari konfigurasi
    $stmt_config = $pdo->query("SELECT value FROM system_configurations WHERE config_key = 'MONTHLY_ADMIN_FEE'");
    $admin_fee = (float)$stmt_config->fetchColumn();
    if ($admin_fee <= 0) {
        echo "Biaya admin tidak diatur atau nol. Proses dihentikan.\n";
        $pdo->commit();
        exit();
    }

    // Ambil semua rekening tabungan aktif
    $stmt_accounts = $pdo->query("SELECT id FROM accounts WHERE account_type = 'TABUNGAN' AND status = 'ACTIVE' AND balance >= $admin_fee");
    $accounts = $stmt_accounts->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($accounts)) {
        echo "Tidak ada rekening yang memenuhi syarat.\n";
        $pdo->commit();
        exit();
    }
    
    $stmt_debit = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
    $stmt_log = $pdo->prepare("INSERT INTO transactions (from_account_id, transaction_type, amount, description, status) VALUES (?, 'BIAYA_ADMIN', ?, ?, 'SUCCESS')");
    $desc = "Biaya Administrasi Bulan " . date('F Y');

    foreach ($accounts as $account_id) {
        $stmt_debit->execute([$admin_fee, $account_id]);
        $stmt_log->execute([$account_id, $admin_fee, $desc]);
    }

    $pdo->commit();
    echo "Selesai. Biaya admin berhasil dipotong dari " . count($accounts) . " rekening.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Error: Proses gagal. " . $e->getMessage() . "\n";
}
?>
