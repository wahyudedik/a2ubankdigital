<?php
// File: app/user_add_withdrawal_account.php
// Penjelasan: Nasabah menambahkan rekening bank eksternal baru.

require_once 'auth_middleware.php';

$input = json_decode(file_get_contents('php://input'), true);
$required = ['bank_name', 'account_number', 'account_name'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO withdrawal_accounts (user_id, bank_name, account_number, account_name) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$authenticated_user_id, $input['bank_name'], $input['account_number'], $input['account_name']]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Rekening penarikan berhasil ditambahkan.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan rekening.']);
}
