<?php
// File: app/beneficiaries_get_list.php
// Penjelasan: Mengambil daftar penerima transfer yang sudah disimpan oleh pengguna.

require_once 'auth_middleware.php';

try {
    $stmt = $pdo->prepare("
        SELECT id, beneficiary_account_number, beneficiary_name, nickname 
        FROM beneficiaries 
        WHERE user_id = ? 
        ORDER BY nickname ASC
    ");
    $stmt->execute([$authenticated_user_id]);
    $beneficiaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $beneficiaries]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
}
?>
