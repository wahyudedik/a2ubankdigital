<?php
// File: app/user_terminate_session.php
// Penjelasan: Nasabah menghentikan sesi login di perangkat lain.

require_once 'auth_middleware.php';

$input = json_decode(file_get_contents('php://input'), true);
$session_id_to_terminate = $input['session_id'] ?? null;

if (!$session_id_to_terminate) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Sesi wajib diisi.']);
    exit();
}

try {
    // Hapus sesi dari database
    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE id = ? AND user_id = ?");
    $stmt->execute([$session_id_to_terminate, $authenticated_user_id]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Sesi berhasil dihentikan.']);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Sesi tidak ditemukan atau bukan milik Anda.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menghentikan sesi.']);
}
?>
