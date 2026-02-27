<?php
// File: app/admin_add_customer.php
// Penjelasan: Staf menambahkan nasabah baru.
// REVISI: Sekarang menerima `unit_id` dari frontend, dengan fallback ke unit staf jika tidak disediakan.

require_once 'auth_middleware.php';

// Semua staf kecuali Nasabah bisa menambah nasabah
if ($authenticated_user_role_id == 9) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

// REVISI: unit_id sekarang wajib di validasi awal
$required = ['full_name', 'email', 'nik', 'mother_maiden_name', 'unit_id'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}

// Password default untuk nasabah yang dibuat oleh staf
$password_plain = 'password123';
$password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
$customer_unit_id = $input['unit_id'];

try {
    $pdo->beginTransaction();

    // 1. Buat user baru (role_id 9 untuk nasabah)
    $stmt_user = $pdo->prepare(
        "INSERT INTO users (role_id, full_name, email, password_hash, phone_number, status, unit_id) 
         VALUES (9, ?, ?, ?, ?, 'ACTIVE', ?)"
    );
    $stmt_user->execute([$input['full_name'], $input['email'], $password_hash, $input['phone_number'], $customer_unit_id]);
    $user_id = $pdo->lastInsertId();

    // 2. Buat profil nasabah
    $stmt_profile = $pdo->prepare(
        "INSERT INTO customer_profiles (user_id, unit_id, nik, mother_maiden_name, pob, dob, gender, address_ktp, registration_method, registered_by, kyc_status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'OFFLINE', ?, 'APPROVED')"
    );
    $stmt_profile->execute([
        $user_id, $customer_unit_id, $input['nik'], $input['mother_maiden_name'], $input['pob'], $input['dob'], 
        $input['gender'], $input['address_ktp'], $authenticated_user_id
    ]);

    // 3. Buat rekening tabungan awal
    $stmt_account = $pdo->prepare(
        "INSERT INTO accounts (user_id, account_type, balance) VALUES (?, 'TABUNGAN', 0)"
    );
    $stmt_account->execute([$user_id]);

    $pdo->commit();
    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Nasabah baru berhasil ditambahkan.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($e->errorInfo[1] == 1062) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Email atau NIK sudah terdaftar.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan nasabah: ' . $e->getMessage()]);
    }
}
