<?php
// File: app/admin_get_dashboard_summary.php
// Penjelasan: REVISI - Memperbaiki logika pengambilan data pertumbuhan nasabah
// agar selalu mengembalikan data untuk 30 hari penuh, termasuk hari dengan 0 pendaftar.

require_once 'auth_middleware.php';

try {
    $summary = [];
    $where_clause_user = ""; // Untuk tabel 'users' atau 'customer_profiles' (alias u/cp)
    $scope_params = [];

    // Terapkan data scoping HANYA jika pengguna bukan Super Admin.
    if ($authenticated_user_role_id !== 1) {
        if (empty($accessible_unit_ids)) {
            // Jika staf tidak punya unit, kirim data kosong agar tidak error.
            echo json_encode(['status' => 'success', 'data' => [
                'kpi' => ['fee_revenue_monthly' => 0, 'new_customers_monthly' => 0, 'outstanding_loan_portfolio' => 0, 'total_customer_funds' => 0],
                'tasks' => ['pendingTopups' => 0, 'pendingWithdrawals' => 0, 'pendingLoans' => 0, 'pendingLoanDisbursements' => 0, 'pendingWithdrawalDisbursements' => 0],
                'recentActivities' => [],
                'customerGrowth' => []
            ]]);
            exit();
        }
        $in_placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        // Klausa 'WHERE' untuk tabel yang memiliki relasi langsung ke customer_profiles atau users
        $where_clause_user = "WHERE cp.unit_id IN ($in_placeholders)";
        $scope_params = $accessible_unit_ids;
    }

    // --- 1. KPI (Key Performance Indicators) ---
    $kpi_params = $scope_params;
    $kpi_where_prefix_and = $where_clause_user ? $where_clause_user . " AND" : "WHERE";

    // Pendapatan Biaya (Bulan ini)
    $stmt_revenue = $pdo->prepare("
        SELECT COALESCE(SUM(t.fee), 0) as total_revenue
        FROM transactions t
        LEFT JOIN accounts a ON t.from_account_id = a.id
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN customer_profiles cp ON u.id = cp.user_id
        " . ($where_clause_user ? $where_clause_user . " AND " : "WHERE ") . " t.status = 'SUCCESS' AND MONTH(t.created_at) = MONTH(CURRENT_DATE()) AND YEAR(t.created_at) = YEAR(CURRENT_DATE())
    ");
    $stmt_revenue->execute($kpi_params);
    $summary['kpi']['fee_revenue_monthly'] = $stmt_revenue->fetchColumn();
    
    // Nasabah Baru Bulan Ini
    $stmt_new_customers = $pdo->prepare("
        SELECT COUNT(u.id) 
        FROM users u
        JOIN customer_profiles cp ON u.id = cp.user_id
        " . $kpi_where_prefix_and . " u.role_id = 9 AND MONTH(u.created_at) = MONTH(CURRENT_DATE()) AND YEAR(u.created_at) = YEAR(CURRENT_DATE())
    ");
    $stmt_new_customers->execute($kpi_params);
    $summary['kpi']['new_customers_monthly'] = $stmt_new_customers->fetchColumn() ?: 0;

    // Total Portofolio Pinjaman Aktif (Outstanding)
    $stmt_loan_portfolio = $pdo->prepare("
        SELECT COALESCE(SUM(l.loan_amount - (SELECT COALESCE(SUM(li.principal_amount), 0) FROM loan_installments li WHERE li.loan_id = l.id AND li.status = 'PAID')), 0)
        FROM loans l
        JOIN users u ON l.user_id = u.id
        JOIN customer_profiles cp ON u.id = cp.user_id
        " . $kpi_where_prefix_and . " l.status = 'DISBURSED'
    ");
    $stmt_loan_portfolio->execute($kpi_params);
    $summary['kpi']['outstanding_loan_portfolio'] = $stmt_loan_portfolio->fetchColumn();

    // Total Dana Nasabah (Tabungan + Deposito)
    $stmt_customer_funds = $pdo->prepare("
        SELECT COALESCE(SUM(a.balance), 0)
        FROM accounts a
        JOIN users u ON a.user_id = u.id
        JOIN customer_profiles cp ON u.id = cp.user_id
        " . $kpi_where_prefix_and . " a.status = 'ACTIVE' AND a.account_type IN ('TABUNGAN', 'DEPOSITO')
    ");
    $stmt_customer_funds->execute($kpi_params);
    $summary['kpi']['total_customer_funds'] = $stmt_customer_funds->fetchColumn();
    
    // --- 2. Tugas Menunggu Persetujuan (Tidak berubah) ---
    // ... (kode tugas tidak diubah)
    $tasks_params = $scope_params;
    $stmt_pending_topups = $pdo->prepare("SELECT COUNT(tr.id) FROM topup_requests tr JOIN users u ON tr.user_id = u.id JOIN customer_profiles cp ON u.id = cp.user_id " . ($where_clause_user ?: "WHERE 1=1") . " AND tr.status = 'PENDING'");
    $stmt_pending_topups->execute($tasks_params);
    $summary['tasks']['pendingTopups'] = $stmt_pending_topups->fetchColumn();
    $stmt_pending_withdrawals = $pdo->prepare("SELECT COUNT(wr.id) FROM withdrawal_requests wr JOIN users u ON wr.user_id = u.id JOIN customer_profiles cp ON u.id = cp.user_id " . ($where_clause_user ?: "WHERE 1=1") . " AND wr.status = 'PENDING'");
    $stmt_pending_withdrawals->execute($tasks_params);
    $summary['tasks']['pendingWithdrawals'] = $stmt_pending_withdrawals->fetchColumn();
    $stmt_pending_loans = $pdo->prepare("SELECT COUNT(l.id) FROM loans l JOIN users u ON l.user_id = u.id JOIN customer_profiles cp ON u.id = cp.user_id " . ($where_clause_user ?: "WHERE 1=1") . " AND l.status = 'SUBMITTED'");
    $stmt_pending_loans->execute($tasks_params);
    $summary['tasks']['pendingLoans'] = $stmt_pending_loans->fetchColumn();
    $stmt_approved_withdrawals = $pdo->prepare("SELECT COUNT(wr.id) FROM withdrawal_requests wr JOIN users u ON wr.user_id = u.id JOIN customer_profiles cp ON u.id = cp.user_id " . ($where_clause_user ?: "WHERE 1=1") . " AND wr.status = 'APPROVED'");
    $stmt_approved_withdrawals->execute($tasks_params);
    $summary['tasks']['pendingWithdrawalDisbursements'] = $stmt_approved_withdrawals->fetchColumn();
    $stmt_approved_loans = $pdo->prepare("SELECT COUNT(l.id) FROM loans l JOIN users u ON l.user_id = u.id JOIN customer_profiles cp ON u.id = cp.user_id " . ($where_clause_user ?: "WHERE 1=1") . " AND l.status = 'APPROVED'");
    $stmt_approved_loans->execute($tasks_params);
    $summary['tasks']['pendingLoanDisbursements'] = $stmt_approved_loans->fetchColumn();

    // --- 3. Aktivitas Terbaru (Tidak berubah) ---
    // ... (kode aktivitas tidak diubah)
     $activity_params = $scope_params;
    $stmt_activity = $pdo->prepare("
        SELECT u.full_name, t.amount, t.transaction_type, t.created_at, t.id
        FROM transactions t
        JOIN accounts a ON t.from_account_id = a.id
        JOIN users u ON a.user_id = u.id
        JOIN customer_profiles cp ON u.id = cp.user_id
        $where_clause_user
        ORDER BY t.created_at DESC LIMIT 5
    ");
    $stmt_activity->execute($activity_params);
    $summary['recentActivities'] = $stmt_activity->fetchAll(PDO::FETCH_ASSOC);


    // --- 4. PERBAIKAN GRAFIK PERTUMBUHAN NASABAH ---
    $growth_params = $scope_params;
    $stmt_growth = $pdo->prepare("
        SELECT DATE(u.created_at) as registration_date, COUNT(u.id) as new_customers
        FROM users u
        JOIN customer_profiles cp ON u.id = cp.user_id
        " . ($where_clause_user ? $where_clause_user . " AND u.role_id = 9" : "WHERE u.role_id = 9") . " AND u.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(u.created_at)
        ORDER BY registration_date ASC
    ");
    $stmt_growth->execute($growth_params);
    $growth_data_raw = $stmt_growth->fetchAll(PDO::FETCH_KEY_PAIR); // Mengambil data sebagai [tanggal => jumlah]

    $customer_growth_complete = [];
    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $customer_growth_complete[] = [
            'registration_date' => $date,
            'new_customers' => (int)($growth_data_raw[$date] ?? 0)
        ];
    }
    $summary['customerGrowth'] = $customer_growth_complete;
    // --- AKHIR PERBAIKAN ---


    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $summary]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data dasbor: ' . $e->getMessage()]);
}
?>

