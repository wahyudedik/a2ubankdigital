<?php
// File: app/crons/process_scheduled_transfers.php
// Penjelasan: Cron job untuk mengeksekusi transfer terjadwal.

if (php_sapi_name() !== 'cli') {
    die("Akses ditolak.");
}
require_once __DIR__ . '/../config.php';

echo "Memulai proses eksekusi transfer terjadwal untuk hari ini...\n";

try {
    // Ambil semua transfer yang dijadwalkan untuk hari ini dan masih PENDING
    $stmt = $pdo->prepare("SELECT * FROM scheduled_transfers WHERE scheduled_date = CURDATE() AND status = 'PENDING' FOR UPDATE");
    $stmt->execute();
    $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($transfers)) {
        echo "Tidak ada transfer terjadwal untuk hari ini.\n";
        exit();
    }
    
    foreach ($transfers as $tf) {
        $pdo->beginTransaction();
        try {
            // Logika transfer sama seperti di `transfer_internal_execute.php`
            // 1. Cek saldo pengirim
            $stmt_bal = $pdo->prepare("SELECT balance FROM accounts WHERE id = ? FOR UPDATE");
            $stmt_bal->execute([$tf['from_account_id']]);
            $balance = (float)$stmt_bal->fetchColumn();
            if ($balance < (float)$tf['amount']) throw new Exception("Saldo tidak mencukupi");
            
            // 2. Cari ID rekening penerima
            $stmt_to = $pdo->prepare("SELECT id FROM accounts WHERE account_number = ? FOR UPDATE");
            $stmt_to->execute([$tf['to_account_number']]);
            $to_account_id = $stmt_to->fetchColumn();
            if (!$to_account_id) throw new Exception("Rekening tujuan tidak ditemukan");

            // 3. Eksekusi
            $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?")->execute([$tf['amount'], $tf['from_account_id']]);
            $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?")->execute([$tf['amount'], $to_account_id]);
            
            // 4. Catat transaksi
            $pdo->prepare("INSERT INTO transactions (from_account_id, to_account_id, transaction_type, amount, description, status) VALUES (?, ?, 'TRANSFER_TERJADWAL', ?, ?, 'SUCCESS')")
                ->execute([$tf['from_account_id'], $to_account_id, $tf['amount'], $tf['description']]);

            // 5. Update status transfer terjadwal
            $pdo->prepare("UPDATE scheduled_transfers SET status = 'EXECUTED', executed_at = NOW() WHERE id = ?")->execute([$tf['id']]);
            
            $pdo->commit();
            echo "Transfer ID #" . $tf['id'] . " berhasil.\n";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            // Update status GAGAL
            $pdo->prepare("UPDATE scheduled_transfers SET status = 'FAILED', failure_reason = ? WHERE id = ?")->execute([$e->getMessage(), $tf['id']]);
            echo "Transfer ID #" . $tf['id'] . " GAGAL: " . $e->getMessage() . "\n";
        }
    }
    echo "Proses selesai.\n";

} catch (Exception $e) {
    echo "Error kritis: " . $e->getMessage() . "\n";
}
?>
