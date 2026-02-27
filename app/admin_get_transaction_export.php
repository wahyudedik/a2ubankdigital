<?php
// File: app/admin_get_transaction_export.php
// Penjelasan: Admin mengekspor data transaksi ke file CSV dengan format yang benar.
// REVISI: Menerapkan data scoping berdasarkan unit staf.

require_once 'auth_middleware.php';

// Hanya peran manajerial yang bisa mengakses
$allowed_roles = [1, 2, 3]; // Super Admin, Kepala Cabang, Kepala Unit
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    $base_sql = "
        FROM transactions t
        LEFT JOIN accounts fa ON t.from_account_id = fa.id
        LEFT JOIN users fu ON fa.user_id = fu.id
        LEFT JOIN customer_profiles fcp ON fu.id = fcp.user_id
        LEFT JOIN accounts ta ON t.to_account_id = ta.id
        LEFT JOIN users tu ON ta.user_id = tu.id
        LEFT JOIN customer_profiles tcp ON tu.id = tcp.user_id
        WHERE DATE(t.created_at) BETWEEN ? AND ?
    ";
    $params = [$start_date, $end_date];

    // LOGIKA BARU: Terapkan Data Scoping
    if ($authenticated_user_role_id !== 1 && !empty($accessible_unit_ids)) {
        $placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        $base_sql .= " AND (fcp.unit_id IN ($placeholders) OR tcp.unit_id IN ($placeholders))";
        $params = array_merge($params, $accessible_unit_ids, $accessible_unit_ids);
    }
    
    $final_sql = "
        SELECT 
            t.transaction_code AS 'Kode Transaksi',
            t.created_at AS 'Tanggal',
            t.transaction_type AS 'Jenis Transaksi',
            t.amount AS 'Jumlah',
            t.fee AS 'Biaya',
            t.description AS 'Deskripsi',
            t.status AS 'Status',
            fa.account_number AS 'Dari Akun',
            fu.full_name AS 'Nama Pengirim',
            ta.account_number AS 'Ke Akun',
            tu.full_name AS 'Nama Penerima'
    " . $base_sql . " ORDER BY t.created_at DESC";
    
    $stmt = $pdo->prepare($final_sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Proses pembuatan CSV (tidak berubah)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="transactions_' . $start_date . '_to_' . $end_date . '.csv"');
    $output = fopen('php://output', 'w');
    if (!empty($transactions)) {
        fputcsv($output, array_keys($transactions[0]));
    }
    foreach ($transactions as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();

} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    error_log("Transaction Export Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuat file ekspor.']);
    exit();
}
