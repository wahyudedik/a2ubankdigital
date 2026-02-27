<?php
// File: app/crons/loan_payment_reminders.php
// Penjelasan: Cron job mengirim pengingat pembayaran cicilan.

if (php_sapi_name() !== 'cli') { die("Akses ditolak."); }
require_once __DIR__ . '/../config.php';
// require_once __DIR__ . '/../helpers/email_helper.php';

echo "Memulai proses pengiriman pengingat jatuh tempo...\n";
$reminder_days = 3; // Kirim pengingat 3 hari sebelum jatuh tempo

try {
    $target_date = date('Y-m-d', strtotime("+$reminder_days days"));
    
    $sql = "
        SELECT li.id, u.email, u.full_name, l.loan_product_name, li.amount, li.due_date
        FROM loan_installments li
        JOIN loans l ON li.loan_id = l.id
        JOIN users u ON l.user_id = u.id
        WHERE li.status = 'PENDING' AND li.due_date = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$target_date]);
    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($reminders)) {
        echo "Tidak ada cicilan yang jatuh tempo pada $target_date.\n";
        exit();
    }
    
    foreach ($reminders as $reminder) {
        echo "Mengirim pengingat ke " . $reminder['email'] . "...\n";
        // Logika pengiriman email atau push notification di sini
        /*
        send_templated_email(
            $reminder['email'], 
            'Pengingat Pembayaran Cicilan', 
            'loan_reminder_template.html', 
            [
                'full_name' => $reminder['full_name'],
                'product_name' => $reminder['loan_product_name'],
                'amount' => number_format($reminder['amount'], 2, ',', '.'),
                'due_date' => $reminder['due_date']
            ]
        );
        */
    }
    echo "Proses selesai.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
