<?php
// File: app/admin_edit_customer.php
// Penjelasan: Staf memperbarui data nasabah.
// REVISI: Menambahkan pencatatan ke log audit.

require_once 'auth_middleware.php';

if ($authenticated_user_role_id == 9) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$customer_id = $input['id'] ?? 0;

if ($customer_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Nasabah wajib diisi.']);
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt_user = $pdo->prepare(
        "UPDATE users SET full_name = ?, email = ?, phone_number = ?, status = ?, unit_id = ? WHERE id = ?"
    );
    $stmt_user->execute([$input['full_name'], $input['email'], $input['phone_number'], $input['status'], $input['unit_id'], $customer_id]);

    $stmt_profile = $pdo->prepare(
        "UPDATE customer_profiles SET nik = ?, mother_maiden_name = ?, pob = ?, dob = ?, gender = ?, address_ktp = ?, unit_id = ? WHERE user_id = ?"
    );
    $stmt_profile->execute([
        $input['nik'], $input['mother_maiden_name'], $input['pob'], $input['dob'], 
        $input['gender'], $input['address_ktp'], $input['unit_id'], $customer_id
    ]);

    // --- PENAMBAHAN LOG AUDIT ---
    $audit_details = json_encode(['edited_customer_id' => $customer_id, 'changed_fields' => array_keys($input)]);
    $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'EDIT_CUSTOMER_PROFILE', ?, ?)");
    $stmt_audit->execute([$authenticated_user_id, $audit_details, $_SERVER['REMOTE_ADDR']]);
    // --- AKHIR PENAMBAHAN ---
    
    $pdo->commit();
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Data nasabah berhasil diperbarui.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e->errorInfo[1] == 1062) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Email atau NIK sudah digunakan oleh nasabah lain.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data nasabah: ' . $e->getMessage()]);
    }
}
