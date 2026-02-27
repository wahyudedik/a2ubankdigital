<?php
// File: app/crons/payout_interest.php
// Penjelasan: Skrip cron job untuk membayarkan akumulasi bunga bulanan.

if (php_sapi_name() !== 'cli') {
    die("Akses ditolak.");
}
require_once __DIR__ . '/../config.php';

echo "Memulai proses pembayaran bunga bulanan...\n";
$previous_month = date('Y-m', strtotime('first day of last month'));

try {
    // Ambil total akumulasi bunga per rekening dari bulan lalu
    $sql = "
        SELECT account_id, SUM(amount) as total_interest
        FROM interest_accruals
        WHERE DATE_FORMAT(calculation_date, '%Y-%m') = ?
        GROUP BY account_id
        HAVING total_interest > 0
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$previous_month]);
    $payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($payouts)) {
        echo "Tidak ada bunga untuk dibayarkan bulan ini.\n";
        exit();
    }

    $pdo->beginTransaction();
    
    $stmt_credit = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
    $stmt_log = $pdo->prepare("INSERT INTO transactions (to_account_id, transaction_type, amount, description, status) VALUES (?, 'BUNGA_TABUNGAN', ?, ?, 'SUCCESS')");

    foreach ($payouts as $payout) {
        // Kreditkan saldo
        $stmt_credit->execute([$payout['total_interest'], $payout['account_id']]);
        // Catat transaksi
        $desc = "Pembayaran Bunga Tabungan Bulan " . date('F Y', strtotime('first day of last month'));
        $stmt_log->execute([$payout['account_id'], $payout['total_interest'], $desc]);
    }

    $pdo->commit();
    echo "Selesai. Bunga bulanan berhasil dibayarkan untuk " . count($payouts) . " rekening.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Error: Proses gagal. " . $e->getMessage() . "\n";
}
?>
