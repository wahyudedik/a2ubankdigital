<?php
// File: app/helpers/receipt_helper.php
// Penjelasan: REVISI FINAL - Menambahkan CASE statement untuk memastikan no. rekening NULL saat bayar angsuran.

/**
 * Mengambil dan memformat data lengkap untuk nota transaksi.
 *
 * @param PDO $pdo Objek koneksi database.
 * @param int $transaction_id ID transaksi yang ingin dicetak.
 * @return array|null Data nota yang sudah diformat, atau null jika tidak ditemukan.
 */
function getReceiptData($pdo, $transaction_id) {
    $sql = "
        SELECT 
            t.id, t.transaction_code, t.transaction_type, t.amount, t.description, t.created_at,
            
            COALESCE(cust_user.full_name, loan_user.full_name) as customer_name,
            
            -- --- PERBAIKAN UTAMA DI SINI ---
            -- Secara eksplisit buat nomor rekening NULL jika ini adalah pembayaran angsuran
            CASE
                WHEN t.transaction_type LIKE '%BAYAR_CICILAN%' THEN NULL
                ELSE cust_acc.account_number
            END as customer_account_number,
            -- --- AKHIR PERBAIKAN ---

            staff_user.full_name as staff_name,
            staff_unit.unit_name,
            staff_unit.address as unit_address,

            l.id as loan_id,
            lp.product_name as loan_product_name,
            li.installment_number
        FROM transactions t
        LEFT JOIN users staff_user ON t.processed_by = staff_user.id
        LEFT JOIN units staff_unit ON staff_user.unit_id = staff_unit.id
        LEFT JOIN loan_installments li ON t.id = li.transaction_id
        LEFT JOIN loans l ON li.loan_id = l.id
        LEFT JOIN loan_products lp ON l.loan_product_id = lp.id
        LEFT JOIN users loan_user ON l.user_id = loan_user.id
        LEFT JOIN accounts cust_acc ON t.to_account_id = cust_acc.id OR t.from_account_id = cust_acc.id
        LEFT JOIN users cust_user ON cust_acc.user_id = cust_user.id
        WHERE t.id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$transaction_id]);
    $receiptData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receiptData) {
        return null;
    }

    // Logika Saldo Awal & Akhir (tidak berubah)
    $initial_balance = null;
    $final_balance = null;

    if (!empty($receiptData['customer_account_number'])) {
        $stmt_balance = $pdo->prepare("SELECT balance FROM accounts WHERE account_number = ?");
        $stmt_balance->execute([$receiptData['customer_account_number']]);
        $current_balance = (float)$stmt_balance->fetchColumn();
        $transaction_amount = (float)$receiptData['amount'];

        switch ($receiptData['transaction_type']) {
            case 'SETOR_TUNAI':
                $final_balance = $current_balance;
                $initial_balance = $current_balance - $transaction_amount;
                break;
            case 'TARIK_TUNAI':
                 $final_balance = $current_balance;
                 $initial_balance = $current_balance + $transaction_amount;
                 break;
        }
    }
    
    $receiptData['initial_balance'] = $initial_balance;
    $receiptData['final_balance'] = $final_balance;
    
    return $receiptData;
}

