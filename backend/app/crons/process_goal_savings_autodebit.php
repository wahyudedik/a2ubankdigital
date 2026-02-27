<?php
// File: app/crons/process_goal_savings_autodebit.php
// Penjelasan: Cron job untuk auto-debit Tabungan Rencana.

if (php_sapi_name() !== 'cli') { die("Akses ditolak."); }
require_once __DIR__ . '/../config.php';

echo "Memulai proses auto-debit Tabungan Rencana...\n";
$today_day = (int)date('d');

try {
    // Ambil semua jadwal auto-debit untuk hari ini
    $sql = "SELECT gsd.*, a.user_id FROM goal_savings_details gsd JOIN accounts a ON gsd.account_id = a.id WHERE gsd.autodebit_day = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$today_day]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($schedules)) {
        echo "Tidak ada jadwal auto-debit untuk hari ini.\n";
        exit();
    }

    foreach ($schedules as $schedule) {
        $pdo->beginTransaction();
        try {
            // Logika transfer dari tabungan utama ke tabungan rencana
            $user_id = $schedule['user_id'];
            $amount = (float)$schedule['autodebit_amount'];
            $to_account_id = $schedule['account_id'];

            // 1. Dapatkan rekening tabungan utama & cek saldo
            $stmt_from = $pdo->prepare("SELECT id, balance FROM accounts WHERE user_id = ? AND account_type = 'TABUNGAN' AND status = 'ACTIVE' FOR UPDATE");
            $stmt_from->execute([$user_id]);
            $from_account = $stmt_from->fetch(PDO::FETCH_ASSOC);
            if (!$from_account || (float)$from_account['balance'] < $amount) throw new Exception("Saldo tidak cukup");

            // 2. Eksekusi
            $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?")->execute([$amount, $from_account['id']]);
            $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?")->execute([$amount, $to_account_id]);
            
            // 3. Catat transaksi
            $desc = "Auto-debit Tabungan: " . $schedule['goal_name'];
            $pdo->prepare("INSERT INTO transactions (from_account_id, to_account_id, transaction_type, amount, description, status) VALUES (?, ?, 'AUTODEBIT_RENCANA', ?, ?, 'SUCCESS')")
                ->execute([$from_account['id'], $to_account_id, $amount, $desc]);

            $pdo->commit();
            echo "Auto-debit untuk user #" . $user_id . " berhasil.\n";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo "Auto-debit untuk user #" . $user_id . " GAGAL: " . $e->getMessage() . "\n";
        }
    }
    echo "Proses selesai.\n";

} catch (Exception $e) {
    echo "Error kritis: " . $e->getMessage() . "\n";
}
?>
