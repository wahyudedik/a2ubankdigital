<?php
// File: app/beneficiaries_add.php
// Penjelasan: Menambahkan rekening tujuan ke daftar penerima transfer.

require_once 'auth_middleware.php';

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['account_number']) || empty($input['nickname'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nomor rekening dan nama panggilan wajib diisi.']);
    exit();
}

$account_number = $input['account_number'];
$nickname = $input['nickname'];

try {
    // 1. Validasi rekening tujuan
    $stmt_inq = $pdo->prepare("SELECT u.full_name, u.id FROM accounts a JOIN users u ON a.user_id = u.id WHERE a.account_number = ? AND a.status = 'ACTIVE'");
    $stmt_inq->execute([$account_number]);
    $recipient = $stmt_inq->fetch(PDO::FETCH_ASSOC);

    if (!$recipient) {
        throw new Exception("Nomor rekening tujuan tidak ditemukan.");
    }
    if ($recipient['id'] == $authenticated_user_id) {
        throw new Exception("Tidak dapat menambahkan rekening sendiri.");
    }

    // 2. Simpan ke database
    $stmt_insert = $pdo->prepare("INSERT INTO beneficiaries (user_id, beneficiary_account_number, beneficiary_name, nickname) VALUES (?, ?, ?, ?)");
    $stmt_insert->execute([$authenticated_user_id, $account_number, $recipient['full_name'], $nickname]);

    http_response_code(201); // Created
    echo json_encode(['status' => 'success', 'message' => 'Penerima berhasil ditambahkan.']);

} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) { // Kode error untuk duplicate entry
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Penerima dengan nomor rekening ini sudah ada di daftar Anda.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan penerima: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
