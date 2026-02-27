<?php
// File: app/user_get_loans.php
// Penjelasan: Nasabah mengambil daftar pinjaman aktif miliknya.
// REVISI: Menggunakan kolom tenor dan tenor_unit yang baru.

require_once 'auth_middleware.php';

if ($authenticated_user_role_id != 9) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

try {
    // REVISI: Mengubah l.tenor_months menjadi l.tenor dan menambahkan l.tenor_unit
    $sql = "SELECT 
                l.id, l.loan_amount, l.tenor, l.tenor_unit, l.disbursement_date,
                lp.product_name
            FROM loans l
            JOIN loan_products lp ON l.loan_product_id = lp.id
            WHERE l.user_id = ? AND l.status = 'DISBURSED'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$authenticated_user_id]);
    $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $loans]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data pinjaman.']);
}
?>
