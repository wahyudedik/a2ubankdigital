<?php
// File: app/user_get_loan_detail.php
// Penjelasan: Nasabah mengambil detail dan jadwal angsuran dari satu pinjaman.
// REVISI: Mengambil kolom tenor dan tenor_unit yang baru.

require_once 'auth_middleware.php';

$loan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($loan_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Pinjaman tidak valid.']);
    exit();
}

try {
    // REVISI: Mengambil kolom `tenor` dan `tenor_unit` secara eksplisit.
    $stmt_loan = $pdo->prepare(
        "SELECT 
            l.id, l.user_id, l.loan_amount, l.status, l.application_date, l.disbursement_date,
            l.interest_rate_pa, l.tenor, l.tenor_unit,
            lp.product_name 
         FROM loans l 
         LEFT JOIN loan_products lp ON l.loan_product_id = lp.id 
         WHERE l.id = ? AND l.user_id = ?"
    );
    $stmt_loan->execute([$loan_id, $authenticated_user_id]);
    $loan_detail = $stmt_loan->fetch(PDO::FETCH_ASSOC);

    if (!$loan_detail) {
        http_response_code(404);
        throw new Exception("Data pinjaman tidak ditemukan atau bukan milik Anda.");
    }
    
    if (empty($loan_detail['product_name'])) {
        $loan_detail['product_name'] = 'Produk Pinjaman (Tidak Aktif)';
    }

    $stmt_installments = $pdo->prepare("SELECT * FROM loan_installments WHERE loan_id = ? ORDER BY installment_number ASC");
    $stmt_installments->execute([$loan_id]);
    $loan_detail['installments'] = $stmt_installments->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $loan_detail]);

} catch (Exception $e) {
    error_log("User Get Loan Detail Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
