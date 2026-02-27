<?php
// File: app/user_get_transaction_history.php
// Penjelasan: REVISI TOTAL - Memperbaiki logika untuk menampilkan SEMUA transaksi
// milik nasabah, termasuk pembayaran cicilan yang diproses oleh staf.
// PERBAIKAN FINAL V2: Memperbaiki error fatal SQLSTATE[HY093] dengan menyusun ulang parameter secara akurat.

require_once 'auth_middleware.php';

// --- Parameter ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
$offset = ($page - 1) * $limit;
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;
$type = $_GET['type'] ?? '';

try {
    // 1. Ambil SEMUA ID rekening milik pengguna yang sedang login
    $stmt_user_accounts = $pdo->prepare("SELECT id FROM accounts WHERE user_id = ?");
    $stmt_user_accounts->execute([$authenticated_user_id]);
    $user_account_ids = $stmt_user_accounts->fetchAll(PDO::FETCH_COLUMN);

    if (empty($user_account_ids)) {
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'pagination' => ['current_page' => 1, 'total_pages' => 0, 'total_records' => 0, 'has_more' => false],
            'data' => []
        ]);
        exit();
    }
    
    // --- Membangun Query dengan Logika Baru ---
    $in_placeholders = implode(',', array_fill(0, count($user_account_ids), '?'));

    $base_sql = "
        FROM transactions t 
        LEFT JOIN accounts from_acc ON t.from_account_id = from_acc.id
        LEFT JOIN users from_user ON from_acc.user_id = from_user.id
        LEFT JOIN loan_installments li ON t.id = li.transaction_id
        LEFT JOIN loans l ON li.loan_id = l.id
    ";
    
    $where_clauses = ["(t.from_account_id IN ($in_placeholders) OR t.to_account_id IN ($in_placeholders) OR l.user_id = ?)"];
    $params_for_where = array_merge($user_account_ids, $user_account_ids, [$authenticated_user_id]);

    if ($start_date && $end_date) {
        $where_clauses[] = "DATE(t.created_at) BETWEEN ? AND ?";
        $params_for_where[] = $start_date;
        $params_for_where[] = $end_date;
    }
    if (!empty($type)) {
        $where_clauses[] = "t.transaction_type = ?";
        $params_for_where[] = $type;
    }

    $where_sql = " WHERE " . implode(" AND ", $where_clauses);

    // --- Query Total ---
    $sql_total = "SELECT COUNT(DISTINCT t.id) " . $base_sql . $where_sql;
    $stmt_total = $pdo->prepare($sql_total);
    $stmt_total->execute($params_for_where);
    $total_records = $stmt_total->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // --- Query Data dengan Deskripsi & Flow yang Disempurnakan ---
    $sql_data = "SELECT 
                    t.id,
                    t.transaction_type, 
                    t.amount,
                    t.status,
                    t.created_at,
                    IF(t.to_account_id IN ($in_placeholders) OR t.transaction_type LIKE 'PENCAIRAN%', 'KREDIT', 'DEBIT') as flow,
                    (CASE
                        WHEN t.to_account_id IN ($in_placeholders) AND t.transaction_type IN ('TRANSFER_INTERNAL', 'TRANSFER_QR') THEN CONCAT('Transfer dari ', from_user.full_name)
                        WHEN t.transaction_type = 'BAYAR_CICILAN_TUNAI' THEN 'Pembayaran Angsuran (via Teller)'
                        WHEN t.transaction_type = 'BAYAR_CICILAN_PAKSA' THEN 'Pembayaran Angsuran (Potong Saldo)'
                        ELSE t.description
                    END) as description
                " . $base_sql . $where_sql . " GROUP BY t.id ORDER BY t.created_at DESC LIMIT ? OFFSET ?";

    // --- PERBAIKAN UTAMA: Membangun parameter yang benar untuk query data ---
    // Gabungkan parameter untuk klausa SELECT dan klausa WHERE
    $params_for_data = array_merge(
        $user_account_ids,      // Untuk IF() di SELECT
        $user_account_ids,      // Untuk WHEN di CASE
        $params_for_where       // Semua parameter dari klausa WHERE
    );
    // Tambahkan parameter untuk LIMIT dan OFFSET
    $params_for_data[] = $limit;
    $params_for_data[] = $offset;

    $stmt_data = $pdo->prepare($sql_data);
    
    // Bind parameter secara manual untuk memastikan tipe data yang benar
    $param_idx = 1;
    foreach ($params_for_data as $param_value) {
        $stmt_data->bindValue($param_idx++, $param_value, is_int($param_value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    
    $stmt_data->execute();
    $transactions = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'pagination' => [
            'current_page' => $page,
            'total_pages' => (int)$total_pages,
            'total_records' => (int)$total_records,
            'has_more' => $page < $total_pages
        ],
        'data' => $transactions
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("User Get History Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil riwayat transaksi: ' . $e->getMessage()]);
}

