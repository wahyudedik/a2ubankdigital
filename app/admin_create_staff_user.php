<?php
// File: app/admin_create_staff_user.php
// Penjelasan: Admin membuat akun staf baru dengan penugasan unit.
// REVISI: Menambahkan pencatatan ke log audit.

require_once 'auth_middleware.php';

$allowed_roles = [1, 2];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$required = ['full_name', 'email', 'role_id', 'unit_id'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}

$full_name = $input['full_name'];
$email = $input['email'];
$role_id = (int)$input['role_id'];
$unit_id = (int)$input['unit_id'];

if ($role_id == 9 || $role_id <= $authenticated_user_role_id) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Peran tidak valid atau Anda tidak dapat membuat admin dengan level yang sama/lebih tinggi.']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    $temp_password = bin2hex(random_bytes(6));
    $password_hash = password_hash($temp_password, PASSWORD_BCRYPT);

    $stmt_insert = $pdo->prepare(
        "INSERT INTO users (role_id, full_name, email, password_hash, status, unit_id) 
         VALUES (?, ?, ?, ?, 'ACTIVE', ?)"
    );
    $stmt_insert->execute([$role_id, $full_name, $email, $password_hash, $unit_id]);
    $new_staff_id = $pdo->lastInsertId();

    // --- PENAMBAHAN LOG AUDIT ---
    $audit_details = json_encode(['new_staff_id' => $new_staff_id, 'email' => $email, 'role_id' => $role_id, 'unit_id' => $unit_id]);
    $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'CREATE_STAFF', ?, ?)");
    $stmt_audit->execute([$authenticated_user_id, $audit_details, $_SERVER['REMOTE_ADDR']]);
    // --- AKHIR PENAMBAHAN ---

    $pdo->commit();

    http_response_code(201);
    echo json_encode([
        'status' => 'success',
        'message' => 'Akun staf berhasil dibuat.',
        'data' => [
            'email' => $email,
            'temporary_password' => $temp_password
        ]
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
     if ($e->errorInfo[1] == 1062) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Email sudah terdaftar.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal membuat akun staf: ' . $e->getMessage()]);
    }
}
