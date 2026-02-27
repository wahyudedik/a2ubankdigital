<?php
// File: app/crons/process_dormant_accounts.php
// Penjelasan: Cron job untuk menandai akun yang tidak aktif (dormant).

if (php_sapi_name() !== 'cli') { die("Akses ditolak."); }
require_once __DIR__ . '/../config.php';

echo "Memulai proses pengecekan akun dormant...\n";
$dormant_threshold_days = 180; // 6 bulan

try {
    // PERBAIKAN: Menambahkan kondisi untuk memastikan hanya akun yang lebih tua
    // dari 180 hari yang akan diperiksa.
    $sql = "
        UPDATE accounts a
        SET a.status = 'DORMANT'
        WHERE a.status = 'ACTIVE'
          AND a.account_type = 'TABUNGAN'
          AND a.created_at < DATE_SUB(NOW(), INTERVAL ? DAY) -- <-- KONDISI BARU YANG PENTING
          AND NOT EXISTS (
            SELECT 1 FROM transactions t
            WHERE (t.from_account_id = a.id OR t.to_account_id = a.id)
              AND t.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
          )
    ";
    
    $stmt = $pdo->prepare($sql);
    // Parameter diikat dua kali untuk kedua placeholder '?'
    $stmt->execute([$dormant_threshold_days, $dormant_threshold_days]);
    
    $affected_rows = $stmt->rowCount();

    echo "Selesai. " . $affected_rows . " akun telah ditandai sebagai dormant.\n";

} catch (Exception $e) {
    echo "Error: Proses gagal. " . $e->getMessage() . "\n";
}
?>
