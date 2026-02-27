<?php
// File: app/user_create_topup_request.php
// Deskripsi: Menyesuaikan path unggahan ke direktori root proyek.

header("Content-Type: application/json");

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/notification_helper.php';

// PERBAIKAN: Path direktori upload sekarang menunjuk ke folder 'uploads' di root proyek.
// dirname(__DIR__) akan menunjuk ke direktori root (satu level di atas 'app').
$upload_dir = dirname(__DIR__) . '/uploads/proofs/';

if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0775, true)) {
         http_response_code(500);
         echo json_encode(['status' => 'error', 'message' => 'Direktori upload tidak ada dan gagal dibuat.']);
         exit();
    }
}
if (!is_writable($upload_dir)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Direktori upload tidak bisa ditulisi.']);
    exit();
}

// Validasi input (tidak berubah)
if (!isset($_POST['amount']) || !is_numeric($_POST['amount']) || $_POST['amount'] <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Jumlah saldo tidak valid.']);
    exit();
}
if (empty($_POST['payment_method'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Metode pembayaran wajib diisi.']);
    exit();
}
if (!isset($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Bukti pembayaran wajib diunggah.']);
    exit();
}

$amount = (float)$_POST['amount'];
$payment_method = $_POST['payment_method'];
$file = $_FILES['proof'];

$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$file_name = 'proof_' . $authenticated_user_id . '_' . time() . '.' . $file_extension;
$target_path = $upload_dir . $file_name;

// PERBAIKAN: Path yang disimpan di database sekarang adalah path web yang benar dari root.
$web_path = '/uploads/proofs/' . $file_name;

if (!move_uploaded_file($file['tmp_name'], $target_path)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memindahkan file bukti yang diunggah.']);
    exit();
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO topup_requests (user_id, amount, payment_method, proof_of_payment_url) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$authenticated_user_id, $amount, $payment_method, $web_path]);
    
    // Logika notifikasi (tidak berubah)
    $title = "Permintaan Isi Saldo Baru";
    $stmt_user_name = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt_user_name->execute([$authenticated_user_id]);
    $customer_name = $stmt_user_name->fetchColumn();
    $message = "Nasabah " . $customer_name . " mengajukan permintaan isi saldo sebesar " . number_format($amount, 0, ',', '.');
    $target_roles = [5, 6];
    
    notify_staff_hierarchically($pdo, $authenticated_user_id, $target_roles, $title, $message);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Permintaan isi saldo Anda telah berhasil dikirim dan akan segera diproses.']);

} catch (Exception $e) {
    if (file_exists($target_path)) {
        unlink($target_path);
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan permintaan: ' . $e->getMessage()]);
}

