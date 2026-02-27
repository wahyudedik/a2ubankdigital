<?php
// File: app/admin_get_teller_report.php
// Penjelasan: Manajer melihat laporan transaksi yang diproses oleh Teller.
// REVISI: Mengganti kolom 't.processed_by' yang tidak ada dengan 't.description' yang berisi ID Teller.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: Kepala Cabang, Kepala Unit, Super Admin
$allowed_roles = [1, 2, 3];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$teller_id = $_GET['teller_id'] ?? null;
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

if (!$teller_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Teller wajib diisi.']);
    exit();
}

try {
    // REVISI: Menggunakan pencarian string pada kolom `description`
    // sebagai pengganti `processed_by`
    $search_pattern = "%Teller #" . $teller_id;
    $base_sql = "
        FROM transactions t
        LEFT JOIN accounts acc ON t.to_account_id = acc.id OR t.from_account_id = acc.id
        LEFT JOIN users u_customer ON acc.user_id = u_customer.id
        LEFT JOIN customer_profiles cp ON u_customer.id = cp.user_id
        WHERE t.description LIKE ? 
          AND DATE(t.created_at) BETWEEN ? AND ?
          AND t.transaction_type IN ('SETOR_TUNAI', 'TARIK_TUNAI')
    ";
    $params = [$search_pattern, $start_date, $end_date];

    // Logika Data Scoping
    if ($authenticated_user_role_id !== 1 && !empty($accessible_unit_ids)) {
        $placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        $base_sql .= " AND cp.unit_id IN ($placeholders)";
        $params = array_merge($params, $accessible_unit_ids);
    }
    
    $final_sql = "
        SELECT 
            t.id, t.created_at, t.transaction_type, t.amount, t.description,
            u_customer.full_name as customer_name,
            acc.account_number
    " . $base_sql . " ORDER BY t.created_at DESC";
    
    $stmt = $pdo->prepare($final_sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Hitung rekapitulasi
    $total_deposit = 0;
    $total_withdrawal = 0;
    foreach($transactions as $trx) {
        if ($trx['transaction_type'] === 'SETOR_TUNAI') {
            $total_deposit += (float)$trx['amount'];
        } else {
            $total_withdrawal += (float)$trx['amount'];
        }
    }

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'summary' => [
            'total_deposit' => $total_deposit,
            'total_withdrawal' => $total_withdrawal,
            'transaction_count' => count($transactions)
        ],
        'data' => $transactions
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil laporan teller: ' . $e->getMessage()]);
}
