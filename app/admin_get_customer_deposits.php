<?php
// File: app/admin_get_customer_deposits.php
// Penjelasan: Mengambil daftar deposito nasabah dengan data scoping & pengecualian untuk Super Admin.
// REVISI: Menambahkan logika untuk bypass filter unit jika role adalah Super Admin.

require_once 'auth_middleware.php';

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'active';

try {
    $summary = [];
    $where_clauses = [];
    $params = [];

    // Terapkan data scoping HANYA jika pengguna bukan Super Admin.
    if ($authenticated_user_role_id !== 1) {
        if (empty($accessible_unit_ids)) {
            // Jika staf (bukan super admin) tidak punya unit, kirim data kosong.
            echo json_encode(['status' => 'success', 'data' => ['summary' => [], 'deposits' => []]]);
            exit();
        }
        $in_placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        $where_clauses[] = "cp.unit_id IN ($in_placeholders)";
        $params = array_merge($params, $accessible_unit_ids);
    }

    // --- Kalkulasi KPI / Ringkasan ---
    $kpi_where_sql = !empty($where_clauses) ? ' AND ' . implode(' AND ', $where_clauses) : '';
    
    $stmt_total_balance = $pdo->prepare("SELECT COALESCE(SUM(a.balance), 0) FROM accounts a JOIN customer_profiles cp ON a.user_id = cp.user_id WHERE a.account_type = 'DEPOSITO' AND a.status = 'ACTIVE' $kpi_where_sql");
    $stmt_total_balance->execute($params);
    $summary['totalActiveBalance'] = $stmt_total_balance->fetchColumn();

    $stmt_total_deposits = $pdo->prepare("SELECT COUNT(a.id) FROM accounts a JOIN customer_profiles cp ON a.user_id = cp.user_id WHERE a.account_type = 'DEPOSITO' $kpi_where_sql");
    $stmt_total_deposits->execute($params);
    $summary['totalDeposits'] = $stmt_total_deposits->fetchColumn();

    $stmt_maturing = $pdo->prepare("SELECT COUNT(a.id) FROM accounts a JOIN customer_profiles cp ON a.user_id = cp.user_id WHERE a.account_type = 'DEPOSITO' AND a.status = 'ACTIVE' AND a.maturity_date BETWEEN CURDATE() AND LAST_DAY(CURDATE()) $kpi_where_sql");
    $stmt_maturing->execute($params);
    $summary['maturingThisMonth'] = $stmt_maturing->fetchColumn();

    // --- Logika Filter dan Pencarian untuk Daftar Utama ---
    if (!empty($search)) {
        $where_clauses[] = "(u.full_name LIKE ? OR a.account_number LIKE ?)";
        $search_term = "%{$search}%";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    switch ($status) {
        case 'near_maturity':
            $where_clauses[] = "a.status = 'ACTIVE' AND a.maturity_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'matured':
            $where_clauses[] = "a.status = 'MATURED'";
            break;
        case 'active':
        default:
            $where_clauses[] = "a.status = 'ACTIVE'";
            break;
    }

    $where_sql = "WHERE " . implode(' AND ', $where_clauses);

    $sql = "
        SELECT 
            a.id, a.account_number, a.balance, a.status, a.created_at as placement_date, a.maturity_date,
            u.full_name as customer_name,
            dp.product_name, dp.interest_rate_pa,
            (a.status = 'ACTIVE' AND a.maturity_date < CURDATE() + INTERVAL 7 DAY) as is_near_maturity
        FROM accounts a
        JOIN users u ON a.user_id = u.id
        JOIN customer_profiles cp ON u.id = cp.user_id
        JOIN deposit_products dp ON a.deposit_product_id = dp.id
        $where_sql
        ORDER BY a.maturity_date ASC
    ";

    $stmt_deposits = $pdo->prepare($sql);
    $stmt_deposits->execute($params);
    $deposits_data = $stmt_deposits->fetchAll(PDO::FETCH_ASSOC);

    foreach ($deposits_data as &$deposit) {
        $principal = (float)$deposit['balance'];
        $rate_pa = (float)$deposit['interest_rate_pa'];
        $placement_date = new DateTime($deposit['placement_date']);
        $today = new DateTime();
        $days_passed = $today->diff($placement_date)->days;
        $maturity_datetime = new DateTime($deposit['maturity_date']);
        if ($today > $maturity_datetime) {
            $days_passed = $maturity_datetime->diff($placement_date)->days;
        }
        $interest_earned = ($principal * ($rate_pa / 100) * $days_passed) / 365;
        $deposit['interest_earned'] = round($interest_earned, 2);
    }
    unset($deposit); // Hapus referensi terakhir

    $response = [
        'summary' => $summary,
        'deposits' => $deposits_data
    ];

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $response]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data deposito: ' . $e->getMessage()]);
}
