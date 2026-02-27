<?php
// File: app/user_update_card_status.php
// Penjelasan: Nasabah memblokir atau membuka blokir kartu mereka.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/email_helper.php';
// require_once __DIR__ . '/helpers/push_notification_helper.php';

$input = json_decode(file_get_contents('php://input'), true);
$card_id = $input['card_id'] ?? null;
$new_status = $input['new_status'] ?? ''; // 'BLOCKED' atau 'ACTIVE'

if (!$card_id || !in_array($new_status, ['BLOCKED', 'ACTIVE'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Input tidak valid.']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE cards SET status = ? WHERE id = ? AND user_id = ? AND status IN ('ACTIVE', 'BLOCKED')");
    $stmt->execute([$new_status, $card_id, $authenticated_user_id]);

    if ($stmt->rowCount() > 0) {
        // Kirim notifikasi keamanan
        $stmt_info = $pdo->prepare("SELECT u.full_name, u.email, c.card_number_masked FROM cards c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
        $stmt_info->execute([$card_id]);
        $card_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

        $subject = "Notifikasi Keamanan: Status Kartu Diubah";
        $email_data = [
            'preheader' => 'Status kartu debit Anda telah diperbarui.',
            'full_name' => $card_info['full_name'],
            'masked_number' => $card_info['card_number_masked'],
            'new_status' => ($new_status === 'BLOCKED' ? 'DIBLOKIR' : 'DIAKTIFKAN KEMBALI'),
            'action_time' => date('d F Y H:i:s')
        ];
        send_email($card_info['email'], $card_info['full_name'], $subject, 'card_status_update_template', $email_data);

        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Status kartu berhasil diperbarui menjadi ' . $new_status . '.']);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Kartu tidak ditemukan atau status tidak dapat diubah.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui status kartu.']);
}
?>
