<?php
// File: app/user_set_card_limit.php
// Penjelasan: Nasabah mengatur limit transaksi kartu.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/email_helper.php';

$input = json_decode(file_get_contents('php://input'), true);
$required = ['card_id', 'daily_limit'];
foreach ($required as $field) {
    if (!isset($input[$field]) || !is_numeric($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi dan harus berupa angka."]);
        exit();
    }
}

try {
    // Validasi kepemilikan kartu
    $stmt_check = $pdo->prepare("SELECT u.full_name, u.email, c.card_number_masked FROM cards c JOIN users u ON c.user_id = u.id WHERE c.id = ? AND c.user_id = ?");
    $stmt_check->execute([$input['card_id'], $authenticated_user_id]);
    $card_info = $stmt_check->fetch(PDO::FETCH_ASSOC);
    if (!$card_info) {
        throw new Exception("Kartu tidak ditemukan atau bukan milik Anda.");
    }

    // Update limit
    $stmt_update = $pdo->prepare("UPDATE cards SET daily_limit = ? WHERE id = ?");
    $stmt_update->execute([$input['daily_limit'], $input['card_id']]);

    // Kirim notifikasi
    $subject = "Notifikasi Perubahan Limit Kartu";
    $email_data = [
        'preheader' => 'Limit transaksi kartu Anda telah diubah.',
        'full_name' => $card_info['full_name'],
        'masked_number' => $card_info['card_number_masked'],
        'new_limit' => number_format($input['daily_limit'], 0, ',', '.'),
        'action_time' => date('d F Y H:i:s')
    ];
    send_email($card_info['email'], $card_info['full_name'], $subject, 'card_limit_update_template', $email_data);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Limit kartu berhasil diperbarui.']);

} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
