<?php
// File: app/dashboard_summary.php
// Penjelasan: Mengambil data ringkas untuk dasbor.
// REVISI FINAL: Memperbaiki logika pengambilan rekening utama dan deskripsi transaksi.

require_once 'auth_middleware.php';

try {
    // 1. Ambil rekening utama (tabungan) nasabah yang AKTIF
    $stmt_account = $pdo->prepare(
        "SELECT id, balance, account_number 
         FROM accounts 
         WHERE user_id = ? AND account_type = 'TABUNGAN' AND status = 'ACTIVE' 
         LIMIT 1"
    );
    $stmt_account->execute([$authenticated_user_id]);
    $account = $stmt_account->fetch(PDO::FETCH_ASSOC);

    // --- PERBAIKAN KRITIS ---
    // Jika tidak ada rekening tabungan aktif, hentikan proses dengan error yang jelas.
    if (!$account) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Rekening tabungan tidak ditemukan.']);
        exit();
    }
    $account_id = $account['id'];
    // --- AKHIR PERBAIKAN ---

    // 2. Ambil 5 transaksi terakhir
    $stmt_transactions = $pdo->prepare("
        SELECT 
            t.transaction_code, 
            t.transaction_type, 
            t.amount, 
            CASE 
                WHEN t.to_account_id = :account_id AND t.transaction_type = 'TRANSFER_INTERNAL' THEN CONCAT('Transfer dari ', from_user.full_name)
                ELSE t.description 
            END as description,
            t.created_at,
            IF(t.to_account_id = :account_id, 'KREDIT', 'DEBIT') as flow
        FROM transactions t
        LEFT JOIN accounts from_acc ON t.from_account_id = from_acc.id
        LEFT JOIN users from_user ON from_acc.user_id = from_user.id
        WHERE t.from_account_id = :account_id OR t.to_account_id = :account_id
        ORDER BY t.created_at DESC
        LIMIT 5
    ");
    $stmt_transactions->execute(['account_id' => $account_id]);
    $transactions = $stmt_transactions->fetchAll(PDO::FETCH_ASSOC);

    // 3. Hitung ringkasan Pemasukan vs. Pengeluaran 7 hari terakhir
    $stmt_weekly = $pdo->prepare("
        SELECT
            DATE(created_at) as transaction_date,
            SUM(CASE WHEN to_account_id = :account_id THEN amount ELSE 0 END) as total_pemasukan,
            SUM(CASE WHEN from_account_id = :account_id THEN amount ELSE 0 END) as total_pengeluaran
        FROM transactions
        WHERE 
            (from_account_id = :account_id OR to_account_id = :account_id)
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            AND status = 'SUCCESS'
        GROUP BY transaction_date
        ORDER BY transaction_date ASC
    ");
    $stmt_weekly->execute(['account_id' => $account_id]);
    $weekly_data = $stmt_weekly->fetchAll(PDO::FETCH_ASSOC);
    
    // Siapkan data untuk grafik
    $labels = [];
    $pemasukan_data = [];
    $pengeluaran_data = [];
    $keyed_data = array_column($weekly_data, null, 'transaction_date');
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $day_label = date('D', strtotime($date));
        $labels[] = $day_label;
        $pemasukan_data[] = (float)($keyed_data[$date]['total_pemasukan'] ?? 0);
        $pengeluaran_data[] = (float)($keyed_data[$date]['total_pengeluaran'] ?? 0);
    }
    $weekly_summary = ['labels' => $labels, 'pemasukan' => $pemasukan_data, 'pengeluaran' => $pengeluaran_data];

    // 4. Hitung ringkasan bulanan
    $stmt_monthly = $pdo->prepare("
        SELECT
            SUM(CASE WHEN to_account_id = :account_id THEN amount ELSE 0 END) as total_pemasukan,
            SUM(CASE WHEN from_account_id = :account_id THEN amount ELSE 0 END) as total_pengeluaran
        FROM transactions
        WHERE 
            (from_account_id = :account_id OR to_account_id = :account_id)
            AND MONTH(created_at) = MONTH(CURRENT_DATE())
            AND YEAR(created_at) = YEAR(CURRENT_DATE())
            AND status = 'SUCCESS'
    ");
    $stmt_monthly->execute(['account_id' => $account_id]);
    $monthly_summary_raw = $stmt_monthly->fetch(PDO::FETCH_ASSOC);
    $monthly_summary = [
        'income' => (float)($monthly_summary_raw['total_pemasukan'] ?? 0),
        'expense' => (float)($monthly_summary_raw['total_pengeluaran'] ?? 0)
    ];

    // Gabungkan semua data untuk respons
    $summary = [
        'account_id' => $account_id,
        'account_number' => $account['account_number'],
        'balance' => $account['balance'],
        'recent_transactions' => $transactions,
        'weekly_summary' => $weekly_summary,
        'monthly_summary' => $monthly_summary
    ];

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $summary]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Dashboard Summary Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
}

