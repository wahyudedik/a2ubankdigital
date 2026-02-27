<?php
// File: app/admin_get_all_loans.php
// REVISI: Memperbaiki tipe data parameter untuk LIMIT dan OFFSET.

require_once 'auth_middleware.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'disbursed';

try {
    $summary = [];
    $where_clauses = [];
    $params = [];
    
    $base_join = "FROM loans l 
                  JOIN users u ON l.user_id = u.id
                  JOIN customer_profiles cp ON u.id = cp.user_id
                  LEFT JOIN loan_products lp ON l.loan_product_id = lp.id
                  LEFT JOIN accounts sav_acc ON u.id = sav_acc.user_id AND sav_acc.account_type = 'TABUNGAN'";

    if ($authenticated_user_role_id !== 1) { 
        if (empty($accessible_unit_ids)) {
            echo json_encode(['status' => 'success', 'data' => [], 'summary' => [], 'pagination' => ['current_page' => 1, 'total_pages' => 0, 'total_records' => 0]]);
            exit();
        }
        $in_placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        $where_clauses[] = "cp.unit_id IN ($in_placeholders)";
        $params = array_merge($params, $accessible_unit_ids);
    }

    $scope_where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';
    $scope_params = $params;

    $kpi_where_prefix_and = $scope_where_sql ? $scope_where_sql . " AND" : " WHERE";
    $stmt_total_active = $pdo->prepare("SELECT COALESCE(SUM(l.loan_amount), 0) $base_join $kpi_where_prefix_and l.status = 'DISBURSED'");
    $stmt_total_active->execute($scope_params);
    $summary['totalActiveLoans'] = $stmt_total_active->fetchColumn();
    $stmt_count_active = $pdo->prepare("SELECT COUNT(l.id) $base_join $kpi_where_prefix_and l.status = 'DISBURSED'");
    $stmt_count_active->execute($scope_params);
    $summary['activeLoansCount'] = $stmt_count_active->fetchColumn();
    $stmt_overdue_count = $pdo->prepare("SELECT COUNT(DISTINCT l.id) $base_join JOIN loan_installments li ON l.id = li.loan_id $kpi_where_prefix_and l.status = 'DISBURSED' AND li.status IN ('PENDING', 'OVERDUE') AND li.due_date < CURDATE()");
    $stmt_overdue_count->execute($scope_params);
    $summary['overdueLoansCount'] = $stmt_overdue_count->fetchColumn();

    if (!empty($search)) {
        $where_clauses[] = "(u.full_name LIKE ? OR l.id LIKE ?)";
        $search_term = "%{$search}%";
        $params[] = $search_term;
        $params[] = $search_term;
    }

    switch ($status_filter) {
        case 'overdue':
             $where_clauses[] = "l.status = 'DISBURSED' AND EXISTS (SELECT 1 FROM loan_installments li WHERE li.loan_id = l.id AND li.status IN ('PENDING', 'OVERDUE') AND li.due_date < CURDATE())";
            break;
        case 'completed':
            $where_clauses[] = "l.status = 'COMPLETED'";
            break;
        case 'disbursed':
        default:
            $where_clauses[] = "l.status = 'DISBURSED'";
            break;
    }

    $where_sql = "WHERE " . implode(' AND ', $where_clauses);

    $sql_total = "SELECT COUNT(DISTINCT l.id) " . $base_join . $where_sql;
    $stmt_total = $pdo->prepare($sql_total);
    $stmt_total->execute($params);
    $total_records = $stmt_total->fetchColumn();
    $total_pages = ceil($total_records / $limit);
    
    $sql_data = "
        SELECT 
            l.id, l.user_id as customer_user_id, l.loan_amount, l.status,
            u.full_name as customer_name,
            lp.product_name,
            sav_acc.balance as customer_savings_balance,
            (l.loan_amount - (SELECT COALESCE(SUM(li.principal_amount), 0) FROM loan_installments li WHERE li.loan_id = l.id AND li.status = 'PAID')) as outstanding_principal,
            (SELECT MIN(li.due_date) FROM loan_installments li WHERE li.loan_id = l.id AND li.status = 'PENDING') as next_due_date,
            (SELECT COUNT(li.id) FROM loan_installments li WHERE li.loan_id = l.id AND li.status IN ('PENDING', 'OVERDUE') AND li.due_date < CURDATE()) as overdue_installments_count
        $base_join
        $where_sql
        GROUP BY l.id
        ORDER BY next_due_date ASC
        LIMIT ? OFFSET ?
    ";
    
    $stmt_loans = $pdo->prepare($sql_data);

    // --- PERBAIKAN UTAMA: Binding parameter dengan tipe data yang benar ---
    $param_index = 1;
    foreach ($params as $param_value) {
        $stmt_loans->bindValue($param_index++, $param_value, PDO::PARAM_STR);
    }
    $stmt_loans->bindValue($param_index++, (int)$limit, PDO::PARAM_INT);
    $stmt_loans->bindValue($param_index, (int)$offset, PDO::PARAM_INT);
    // --- AKHIR PERBAIKAN ---

    $stmt_loans->execute();
    $loans_data = $stmt_loans->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'data' => $loans_data,
        'summary' => $summary,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => (int)$total_pages,
            'total_records' => (int)$total_records
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data pinjaman: ' . $e->getMessage()]);
}
?>

