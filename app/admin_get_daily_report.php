<?php
// File: app/admin_get_daily_report.php
// Penjelasan: Mengambil ringkasan transaksi untuk laporan harian manajemen.
// REVISI: Menerapkan data scoping berdasarkan unit staf.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: Kepala Unit, Kepala Cabang, Super Admin
$allowed_roles = [1, 2, 3];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$report_date = $_GET['date'] ?? date('Y-m-d');

try {
    // --- Membangun Query dengan Data Scoping ---
    $base_sql = "
        FROM transactions t
        LEFT JOIN accounts a_from ON t.from_account_id = a_from.id
        LEFT JOIN customer_profiles cp_from ON a_from.user_id = cp_from.user_id
        LEFT JOIN accounts a_to ON t.to_account_id = a_to.id
        LEFT JOIN customer_profiles cp_to ON a_to.user_id = cp_to.user_id
    ";
    $where_clauses = ["DATE(t.created_at) = ?"];
    $params = [$report_date];

    // --- LOGIKA DATA SCOPING ---
    // Terapkan filter unit/cabang jika yang login BUKAN Super Admin.
    if ($authenticated_user_role_id !== 1) {
        if (empty($accessible_unit_ids)) {
            // Jika staf tidak punya akses, kirim data kosong.
            echo json_encode(['status' => 'success', 'data' => ['summary' => ['total_debit' => 0, 'total_credit' => 0], 'details' => []]]);
            exit();
        }
        $placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        // Filter transaksi jika unit nasabah (baik pengirim ATAU penerima) ada dalam lingkup staf.
        $where_clauses[] = "(cp_from.unit_id IN ($placeholders) OR cp_to.unit_id IN ($placeholders))";
        $params = array_merge($params, $accessible_unit_ids, $accessible_unit_ids);
    }
    
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);

    $final_sql = "
        SELECT 
            t.transaction_type, 
            COUNT(t.id) as total_transactions, 
            SUM(t.amount) as total_amount
    " . $base_sql . $where_sql . " GROUP BY t.transaction_type";
    
    $stmt = $pdo->prepare($final_sql);
    $stmt->execute($params);
    $summary_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Logika kalkulasi (tidak berubah)
    $total_debit = 0;
    $total_credit = 0;
    $debit_types = ['TARIK_TUNAI', 'TRANSFER_INTERNAL', 'TRANSFER_EKSTERNAL', 'PEMBELIAN_PRODUK', 'PEMBAYARAN_TAGIHAN', 'BIAYA_ADMIN', 'BAYAR_CICILAN', 'PEMBUKAAN_DEPOSITO'];
    $credit_types = ['SETOR_TUNAI', 'TRANSFER_INTERNAL', 'PENCAIRAN_PINJAMAN', 'BUNGA_TABUNGAN', 'PENCAIRAN_DEPOSITO'];
    $detailed_summary = [];

    foreach ($summary_data as $row) {
        $amount = (float)$row['total_amount'];
        $detailed_summary[$row['transaction_type']] = [
            'count' => (int)$row['total_transactions'],
            'amount' => $amount
        ];
        if (in_array($row['transaction_type'], $debit_types)) {
            $total_debit += $amount;
        }
        if (in_array($row['transaction_type'], $credit_types)) {
            // Khusus transfer internal, dihitung sebagai kredit juga
            $total_credit += $amount;
        }
    }
    
    $response = [
        'report_date' => $report_date,
        'summary' => [
            'total_debit' => $total_debit,
            'total_credit' => $total_credit
        ],
        'details' => $detailed_summary
    ];

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $response]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil laporan: ' . $e->getMessage()]);
}
