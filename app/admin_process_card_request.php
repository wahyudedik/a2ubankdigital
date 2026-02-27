<?php
// File: app/admin_process_card_request.php
// Penjelasan: Admin menyetujui dan mengaktifkan kartu nasabah.
// REVISI: Menambahkan pencatatan ke log audit dan validasi data scoping.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/email_helper.php';

if ($authenticated_user_role_id > 6) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$card_id = $input['card_id'] ?? null;
$action = $input['action'] ?? ''; // 'APPROVE'

if (!$card_id || $action !== 'APPROVE') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Input tidak valid.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Query dimodifikasi untuk mengambil unit_id nasabah
    $stmt_info = $pdo->prepare("
        SELECT u.full_name, u.email, c.card_number_masked, cp.unit_id
        FROM cards c 
        JOIN users u ON c.user_id = u.id
        JOIN customer_profiles cp ON u.id = cp.user_id
        WHERE c.id = ? AND c.status = 'REQUESTED' FOR UPDATE
    ");
    $stmt_info->execute([$card_id]);
    $card_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$card_info) {
        throw new Exception("Pengajuan kartu tidak ditemukan atau sudah diproses.");
    }

    // --- VALIDASI DATA SCOPING ---
    // Memastikan staf hanya bisa memproses nasabah di unit kerjanya
    if ($authenticated_user_role_id !== 1 && !in_array($card_info['unit_id'], $accessible_unit_ids)) {
        http_response_code(403);
        throw new Exception("Akses ditolak: Anda tidak berwenang memproses permintaan kartu untuk nasabah di unit ini.");
    }
    // --- AKHIR VALIDASI ---

    $expiry_date = date('Y-m-d', strtotime('+5 years'));

    $stmt_update = $pdo->prepare("UPDATE cards SET status = 'ACTIVE', activated_at = NOW(), expiry_date = ? WHERE id = ?");
    $stmt_update->execute([$expiry_date, $card_id]);

    // --- PENAMBAHAN LOG AUDIT ---
    $audit_details = json_encode(['approved_card_id' => $card_id]);
    $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'APPROVE_CARD_REQUEST', ?, ?)");
    $stmt_audit->execute([$authenticated_user_id, $audit_details, $_SERVER['REMOTE_ADDR']]);
    // --- AKHIR PENAMBAHAN ---

    $email_data = [
        'preheader' => 'Kabar Baik! Kartu Debit Anda Telah Aktif.',
        'full_name' => $card_info['full_name'],
        'masked_number' => $card_info['card_number_masked'],
        'activation_date' => date('d F Y')
    ];
    send_email($card_info['email'], $card_info['full_name'], 'Kartu Debit Anda Telah Diaktifkan', 'card_activated_template', $email_data);

    $pdo->commit();

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Kartu berhasil diaktifkan.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // Mengirimkan response code yang sesuai jika ada error otorisasi
    $code = http_response_code() >= 400 ? http_response_code() : 500;
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memproses kartu: ' . $e->getMessage()]);
}
?>

