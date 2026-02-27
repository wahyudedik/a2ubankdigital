<?php
// File: app/crons/process_standing_instructions.php
// Penjelasan: Cron job mengeksekusi transfer rutin.

if (php_sapi_name() !== 'cli') { die("Akses ditolak."); }
require_once __DIR__ . '/../config.php';

echo "Memulai proses eksekusi instruksi berulang...\n";
$today = date('Y-m-d');
$day_of_month = (int)date('d');
$day_of_week = (int)date('N'); // 1 (Mon) - 7 (Sun)

try {
    // Ambil semua instruksi yang aktif dan jadwalnya hari ini
    $sql = "
        SELECT * FROM standing_instructions 
        WHERE status = 'ACTIVE' 
        AND start_date <= CURDATE() 
        AND (end_date IS NULL OR end_date >= CURDATE())
        AND (last_executed IS NULL OR last_executed < CURDATE())
        AND (
            (frequency = 'MONTHLY' AND execution_day = ?) OR
            (frequency = 'WEEKLY' AND execution_day = ?)
        )
        FOR UPDATE
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$day_of_month, $day_of_week]);
    $instructions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($instructions)) {
        echo "Tidak ada instruksi untuk dieksekusi hari ini.\n";
        exit();
    }
    
    foreach ($instructions as $inst) {
        // Logika transfer (mirip dengan transfer terjadwal)
        // ... (cek saldo, debit, kredit/panggil API eksternal, catat transaksi)
        // Jika berhasil:
        $pdo->prepare("UPDATE standing_instructions SET last_executed = CURDATE() WHERE id = ?")->execute([$inst['id']]);
        echo "Instruksi #" . $inst['id'] . " berhasil dieksekusi.\n";
    }
    echo "Proses selesai.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
