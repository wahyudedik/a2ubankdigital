<?php
// File: app/user_get_transaction_detail.php
// Penjelasan: REVISI TOTAL - Memperbaiki logika hak akses agar nasabah bisa melihat
// detail transaksi yang terkait dengan semua rekeningnya, termasuk pinjaman.
// PERBAIKAN FINAL V2: Memperbaiki error fatal SQLSTATE[HY093] dengan menyusun parameter secara akurat.

require_once 'auth_middleware.php';

$transaction_id = $_GET['id'] ?? null;
if (!$transaction_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Transaksi wajib diisi.']);
    exit();
}

try {
    // 1. Ambil semua ID rekening milik pengguna yang sedang login
    $stmt_user_accounts = $pdo->prepare("SELECT id FROM accounts WHERE user_id = ?");
    $stmt_user_accounts->execute([$authenticated_user_id]);
    $user_account_ids = $stmt_user_accounts->fetchAll(PDO::FETCH_COLUMN);

    if (empty($user_account_ids)) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada rekening yang ditemukan untuk pengguna ini.']);
        exit();
    }
    
    // 2. Siapkan placeholder untuk klausa IN() yang aman
    $in_placeholders = implode(',', array_fill(0, count($user_account_ids), '?'));

    // --- Query diperluas untuk mencakup kepemilikan pinjaman ---
    $sql = "
        SELECT
            t.id, t.transaction_code, t.transaction_type, t.amount, t.fee,
            t.status, t.created_at,
            from_acc.account_number as from_account_number,
            from_user.full_name as from_user_name,
            to_acc.account_number as to_account_number,
            to_user.full_name as to_user_name,
            (CASE
                WHEN t.to_account_id IN ($in_placeholders) AND t.transaction_type IN ('TRANSFER_INTERNAL', 'TRANSFER_QR') THEN CONCAT('Transfer dari ', from_user.full_name)
                WHEN t.transaction_type = 'BAYAR_CICILAN_TUNAI' THEN 'Pembayaran Angsuran (via Teller)'
                WHEN t.transaction_type = 'BAYAR_CICILAN_PAKSA' THEN 'Pembayaran Angsuran (Potong Saldo)'
                ELSE t.description
            END) as description
        FROM transactions t
        LEFT JOIN accounts from_acc ON t.from_account_id = from_acc.id
        LEFT JOIN users from_user ON from_acc.user_id = from_user.id
        LEFT JOIN accounts to_acc ON t.to_account_id = to_acc.id
        LEFT JOIN users to_user ON to_acc.user_id = to_user.id
        LEFT JOIN loan_installments li ON t.id = li.transaction_id
        LEFT JOIN loans l ON li.loan_id = l.id
        WHERE t.id = ?
          AND (
            t.from_account_id IN ($in_placeholders) 
            OR t.to_account_id IN ($in_placeholders)
            OR l.user_id = ?
          )
    ";

    // --- PERBAIKAN UTAMA: Menyusun array parameter dengan urutan yang benar ---
    $params = array_merge(
        $user_account_ids,      // Parameter untuk klausa CASE ... IN (...)
        [$transaction_id],      // Parameter untuk t.id = ?
        $user_account_ids,      // Parameter untuk from_account_id IN (...)
        $user_account_ids,      // Parameter untuk to_account_id IN (...)
        [$authenticated_user_id] // Parameter untuk l.user_id = ?
    );
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    // 5. Kirim respons
    if ($transaction) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'data' => $transaction]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Transaksi tidak ditemukan atau Anda tidak memiliki akses.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("User Get Transaction Detail Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
}

