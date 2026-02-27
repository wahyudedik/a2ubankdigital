<?php
// File: app/admin_marketing_send_promo.php
// Penjelasan: Staf Marketing mengirim notifikasi promo ke nasabah.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: Marketing, Super Admin
$allowed_roles = [1, 4];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$required = ['title', 'message'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}

$title = $input['title'];
$message = $input['message'];

try {
    // 1. Dapatkan semua ID nasabah aktif
    $stmt_users = $pdo->prepare("SELECT id FROM users WHERE role_id = 9 AND status = 'ACTIVE'");
    $stmt_users->execute();
    $customer_ids = $stmt_users->fetchAll(PDO::FETCH_COLUMN);

    if (empty($customer_ids)) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Tidak ada nasabah aktif untuk dikirimi notifikasi.']);
        exit();
    }

    // 2. Insert notifikasi secara massal
    $pdo->beginTransaction();
    $stmt_insert = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'PROMO')");
    foreach ($customer_ids as $user_id) {
        $stmt_insert->execute([$user_id, $title, $message]);
    }
    $pdo->commit();
    
    // Di aplikasi nyata, proses ini idealnya dijalankan sebagai background job
    // dan juga memicu push notification ke perangkat nasabah.

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Notifikasi promo berhasil dikirim ke ' . count($customer_ids) . ' nasabah.']);

} catch (PDOException $e) {
    if($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim notifikasi: ' . $e->getMessage()]);
}
?>
