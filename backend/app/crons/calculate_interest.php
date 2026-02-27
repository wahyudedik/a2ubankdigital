<?php
// File: app/crons/calculate_interest.php
// Penjelasan: Skrip cron job untuk mengakumulasi bunga harian.

// Skrip ini harus dijalankan dari command line, bukan browser.
if (php_sapi_name() !== 'cli') {
    die("Akses ditolak. Skrip ini hanya untuk command line.");
}

require_once __DIR__ . '/../config.php';

echo "Memulai proses kalkulasi bunga harian...\n";

try {
    $pdo->beginTransaction();

    // Ambil semua rekening tabungan aktif dengan saldo positif
    $stmt = $pdo->query("
        SELECT a.id, a.balance, sc.value as interest_rate_pa
        FROM accounts a
        JOIN system_configurations sc ON sc.config_key = 'SAVINGS_INTEREST_RATE_PA'
        WHERE a.account_type = 'TABUNGAN' AND a.status = 'ACTIVE' AND a.balance > 0
    ");
    
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($accounts)) {
        echo "Tidak ada rekening yang memenuhi syarat untuk diproses.\n";
        $pdo->commit();
        exit();
    }
    
    $interest_rate_pa = (float)$accounts[0]['interest_rate_pa'];
    $daily_rate = ($interest_rate_pa / 100) / 365;

    $stmt_insert = $pdo->prepare("INSERT INTO interest_accruals (account_id, amount, calculation_date) VALUES (?, ?, CURDATE())");

    foreach ($accounts as $account) {
        $daily_interest = (float)$account['balance'] * $daily_rate;
        $stmt_insert->execute([$account['id'], $daily_interest]);
    }
    
    $pdo->commit();
    echo "Selesai. Bunga harian berhasil dihitung untuk " . count($accounts) . " rekening.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Error: Proses gagal. " . $e->getMessage() . "\n";
}
?>
