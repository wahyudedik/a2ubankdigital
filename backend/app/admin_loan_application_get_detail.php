<?php
// File: app/admin_loan_application_get_detail.php
// Penjelasan: Mengambil detail lengkap dari satu pengajuan pinjaman.
// REVISI: Mengambil kolom tenor dan tenor_unit yang baru.

require_once 'auth_middleware.php';

if ($authenticated_user_role_id == 9) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$loan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($loan_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Pinjaman tidak valid.']);
    exit();
}

try {
    // REVISI: Mengambil kolom `tenor` dan `tenor_unit` secara eksplisit.
    $sql = "SELECT 
                l.id, l.user_id, l.account_id, l.loan_product_id, l.loan_amount, 
                l.interest_rate_pa, l.status, l.application_date, l.approval_date, 
                l.disbursement_date, l.approved_by, l.tenor, l.tenor_unit,
                u.full_name as customer_name, u.email, u.phone_number,
                lp.product_name,
                approver.full_name as approver_name
            FROM loans l
            JOIN users u ON l.user_id = u.id
            JOIN loan_products lp ON l.loan_product_id = lp.id
            LEFT JOIN users approver ON l.approved_by = approver.id
            WHERE l.id = :loan_id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':loan_id', $loan_id, PDO::PARAM_INT);
    $stmt->execute();
    $loan_detail = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$loan_detail) {
        http_response_code(404);
        throw new Exception("Data pengajuan pinjaman tidak ditemukan.");
    }
    
    if ($loan_detail['status'] === 'DISBURSED' || $loan_detail['status'] === 'COMPLETED') {
        $stmt_installments = $pdo->prepare("SELECT * FROM loan_installments WHERE loan_id = :loan_id ORDER BY installment_number ASC");
        $stmt_installments->bindParam(':loan_id', $loan_id, PDO::PARAM_INT);
        $stmt_installments->execute();
        $loan_detail['installments'] = $stmt_installments->fetchAll(PDO::FETCH_ASSOC);
    }

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $loan_detail]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
