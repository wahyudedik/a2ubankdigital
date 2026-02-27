<?php
// File: app/crons/process_late_fees.php
// Penjelasan: Cron job harian untuk memeriksa dan menerapkan denda pada angsuran yang terlambat.

// Hanya bisa dijalankan dari command line (CLI)
if (php_sapi_name() !== 'cli') {
    die("Akses ditolak.");
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers/log_helper.php';

echo "Memulai proses kalkulasi denda keterlambatan harian...\n";
log_system_event($pdo, 'INFO', 'CRON_START', ['job' => 'process_late_fees']);

try {
    // 1. Ambil semua angsuran yang sudah jatuh tempo (due_date < HARI INI)
    //    dan statusnya belum lunas (PENDING atau OVERDUE).
    //    Kita juga langsung ambil denda harian dari produk pinjaman terkait.
    $sql = "
        SELECT 
            li.id, 
            li.status,
            lp.late_payment_fee
        FROM loan_installments li
        JOIN loans l ON li.loan_id = l.id
        JOIN loan_products lp ON l.loan_product_id = lp.id
        WHERE 
            li.due_date < CURDATE() 
            AND li.status IN ('PENDING', 'OVERDUE')
            AND lp.late_payment_fee > 0
    ";
    
    $stmt = $pdo->query($sql);
    $overdue_installments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($overdue_installments)) {
        echo "Tidak ada angsuran yang terlambat ditemukan hari ini.\n";
        log_system_event($pdo, 'INFO', 'CRON_FINISH', ['job' => 'process_late_fees', 'result' => 'No overdue installments.']);
        exit();
    }

    $processed_count = 0;
    
    // 2. Proses setiap angsuran yang terlambat
    foreach ($overdue_installments as $installment) {
        try {
            $pdo->beginTransaction();

            // a. Tambahkan denda harian ke total denda yang sudah ada
            $stmt_add_penalty = $pdo->prepare(
                "UPDATE loan_installments 
                 SET penalty_amount = penalty_amount + ? 
                 WHERE id = ?"
            );
            $stmt_add_penalty->execute([$installment['late_payment_fee'], $installment['id']]);

            // b. Jika statusnya masih PENDING, ubah menjadi OVERDUE
            if ($installment['status'] === 'PENDING') {
                $stmt_update_status = $pdo->prepare(
                    "UPDATE loan_installments 
                     SET status = 'OVERDUE' 
                     WHERE id = ?"
                );
                $stmt_update_status->execute([$installment['id']]);
            }

            $pdo->commit();
            $processed_count++;
            echo "Denda ditambahkan untuk angsuran ID: " . $installment['id'] . "\n";

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error_message = "Gagal memproses denda untuk angsuran ID " . $installment['id'] . ": " . $e->getMessage();
            echo $error_message . "\n";
            log_system_event($pdo, 'ERROR', 'CRON_JOB_ERROR', ['job' => 'process_late_fees', 'error' => $error_message]);
        }
    }

    echo "Proses selesai. Denda berhasil ditambahkan ke " . $processed_count . " angsuran.\n";
    log_system_event($pdo, 'INFO', 'CRON_FINISH', ['job' => 'process_late_fees', 'result' => "Processed $processed_count installments."]);

} catch (Exception $e) {
    $fatal_error = "Error kritis saat proses denda: " . $e->getMessage();
    echo $fatal_error . "\n";
    log_system_event($pdo, 'CRITICAL', 'CRON_JOB_FATAL', ['job' => 'process_late_fees', 'error' => $fatal_error]);
}
