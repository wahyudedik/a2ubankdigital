<?php
// File: app/beneficiaries_delete.php
// Penjelasan: Menghapus penerima transfer dari daftar pengguna.

require_once 'auth_middleware.php';

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID penerima wajib diisi.']);
    exit();
}

$beneficiary_id = $input['id'];

try {
    $stmt = $pdo->prepare("DELETE FROM beneficiaries WHERE id = ? AND user_id = ?");
    $stmt->execute([$beneficiary_id, $authenticated_user_id]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Penerima berhasil dihapus.']);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Penerima tidak ditemukan atau Anda tidak memiliki akses.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
}
?>
