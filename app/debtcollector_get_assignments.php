<?php
// File: app/debtcollector_get_assignments.php
// Penjelasan: Debt Collector mengambil daftar tugas penagihan.
// REVISI: Mengganti kolom 'loan_account_id' dengan 'loan_id' yang benar.

require_once 'auth_middleware.php';

// Hanya Debt Collector (role_id = 8) yang bisa mengakses
if ($authenticated_user_role_id !== 8) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            da.id as assignment_id,
            u.full_name,
            u.phone_number,
            cp.address_domicile,
            li.due_date,
            li.amount_due,
            da.status
        FROM debt_collection_assignments da
        JOIN loan_installments li ON da.loan_installment_id = li.id
        JOIN loans l ON li.loan_id = l.id
        JOIN users u ON l.user_id = u.id
        JOIN customer_profiles cp ON u.id = cp.user_id
        WHERE da.collector_id = ? AND da.status = 'ASSIGNED'
        ORDER BY li.due_date ASC
    ");
    $stmt->execute([$authenticated_user_id]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $assignments]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data tugas: ' . $e->getMessage()]);
}
