<?php
// File: app/admin_get_customer_detail.php
// Penjelasan: Mengambil detail lengkap nasabah, termasuk path foto KTP dan selfie.
// REVISI: Menambahkan tenor dan tenor_unit pada data pinjaman.

require_once 'auth_middleware.php';

if ($authenticated_user_role_id == 9) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($customer_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Nasabah tidak valid.']);
    exit();
}

try {
    // 1. Ambil data profil utama nasabah, tambahkan ktp_image_path dan selfie_image_path
    $stmt_profile = $pdo->prepare("
        SELECT 
            u.id, u.bank_id, u.full_name, u.email, u.phone_number, u.status, u.created_at,
            cp.nik, cp.mother_maiden_name, cp.pob, cp.dob, cp.gender, cp.address_ktp, cp.address_domicile, cp.occupation,
            cp.ktp_image_path, cp.selfie_image_path,
            cp.unit_id,
            un.unit_name as unit_name,
            b.unit_name as branch_name
        FROM users u
        LEFT JOIN customer_profiles cp ON u.id = cp.user_id
        LEFT JOIN units un ON cp.unit_id = un.id
        LEFT JOIN units b ON un.parent_id = b.id
        WHERE u.id = ? AND u.role_id = 9
    ");
    $stmt_profile->execute([$customer_id]);
    $customer_data = $stmt_profile->fetch(PDO::FETCH_ASSOC);

    if (!$customer_data) {
        http_response_code(404);
        throw new Exception('Nasabah tidak ditemukan.');
    }

    // 2. Ambil semua rekening nasabah, termasuk detail produk deposito
    $stmt_accounts = $pdo->prepare("
        SELECT 
            a.id, a.account_number, a.account_type, a.balance, a.status, a.created_at, a.maturity_date,
            dp.product_name as deposit_product_name, dp.interest_rate_pa
        FROM accounts a
        LEFT JOIN deposit_products dp ON a.deposit_product_id = dp.id
        WHERE a.user_id = ?
    ");
    $stmt_accounts->execute([$customer_id]);
    $customer_data['accounts'] = $stmt_accounts->fetchAll(PDO::FETCH_ASSOC);

    // Hitung bunga berjalan untuk setiap deposito aktif
    foreach ($customer_data['accounts'] as &$account) {
        if ($account['account_type'] === 'DEPOSITO' && $account['status'] === 'ACTIVE' && !empty($account['interest_rate_pa'])) {
            $principal = (float)$account['balance'];
            $rate_pa = (float)$account['interest_rate_pa'];
            $placement_date = new DateTime($account['created_at']);
            $today = new DateTime();
            $days_passed = $today->diff($placement_date)->days;
            $maturity_datetime = new DateTime($account['maturity_date']);
            
            if ($today > $maturity_datetime) {
                $days_passed = $maturity_datetime->diff($placement_date)->days;
            }
            
            $interest_earned = ($principal * ($rate_pa / 100) * $days_passed) / 365;
            $account['interest_earned'] = round($interest_earned, 2);
        }
    }
    unset($account);

    // 3. Ambil semua pinjaman nasabah
    // REVISI: Menambahkan l.tenor dan l.tenor_unit
    $stmt_loans = $pdo->prepare("
        SELECT l.id, l.loan_amount, l.status, l.application_date, l.tenor, l.tenor_unit, lp.product_name
        FROM loans l
        JOIN loan_products lp ON l.loan_product_id = lp.id
        WHERE l.user_id = ?
    ");
    $stmt_loans->execute([$customer_id]);
    $customer_data['loans'] = $stmt_loans->fetchAll(PDO::FETCH_ASSOC);

    // 4. Untuk setiap pinjaman, ambil detail angsurannya
    if (!empty($customer_data['loans'])) {
        $stmt_installments = $pdo->prepare("SELECT * FROM loan_installments WHERE loan_id = ? ORDER BY installment_number ASC");
        foreach ($customer_data['loans'] as &$loan) {
            $stmt_installments->execute([$loan['id']]);
            $loan['installments'] = $stmt_installments->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($loan);
    }

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $customer_data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

