<?php
// File: app/crons/process_loyalty_points.php
// Penjelasan: Cron job memberikan poin loyalitas harian.

if (php_sapi_name() !== 'cli') { die("Akses ditolak."); }
require_once __DIR__ . '/../config.php';

echo "Memulai proses pemberian poin loyalitas...\n";
$yesterday = date('Y-m-d', strtotime('-1 day'));

try {
    // Ambil semua transaksi pembayaran & pembelian kemarin yang belum diproses poinnya
    // Di sistem nyata, akan ada flag `is_points_processed` di tabel `transactions`
    $sql = "SELECT from_account_id, amount, transaction_type FROM transactions WHERE DATE(created_at) = ? AND transaction_type IN ('PEMBELIAN_PRODUK', 'PEMBAYARAN_TAGIHAN')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$yesterday]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $user_points = [];
    foreach ($transactions as $tx) {
        // Logika Poin: 1 poin untuk setiap kelipatan Rp 10.000
        $points_earned = floor((float)$tx['amount'] / 10000);
        if ($points_earned > 0) {
            $stmt_user = $pdo->prepare("SELECT user_id FROM accounts WHERE id = ?");
            $stmt_user->execute([$tx['from_account_id']]);
            $user_id = $stmt_user->fetchColumn();
            if ($user_id) {
                if (!isset($user_points[$user_id])) $user_points[$user_id] = 0;
                $user_points[$user_id] += $points_earned;
            }
        }
    }
    
    foreach ($user_points as $user_id => $total_points) {
        $pdo->prepare("UPDATE users SET loyalty_points_balance = loyalty_points_balance + ? WHERE id = ?")->execute([$total_points, $user_id]);
        $pdo->prepare("INSERT INTO loyalty_points_history (user_id, points, description) VALUES (?, ?, ?)")->execute([$user_id, $total_points, "Poin dari transaksi tanggal " . $yesterday]);
        echo "Memberikan " . $total_points . " poin kepada user #" . $user_id . "\n";
    }
    echo "Proses selesai.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
