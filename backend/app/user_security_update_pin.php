<?php
// File: app/user_security_update_pin.php
// Penjelasan: Nasabah mengatur atau mengubah PIN transaksi.

require_once 'auth_middleware.php';

$input = json_decode(file_get_contents('php://input'), true);

$old_pin = $input['old_pin'] ?? null;
$new_pin = $input['new_pin'] ?? '';
$confirm_pin = $input['confirm_pin'] ?? '';

// Validasi input
if (empty($new_pin) || empty($confirm_pin)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'PIN baru dan konfirmasi PIN wajib diisi.']);
    exit();
}
if (strlen($new_pin) != 6 || !is_numeric($new_pin)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'PIN harus terdiri dari 6 digit angka.']);
    exit();
}
if ($new_pin !== $confirm_pin) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'PIN baru dan konfirmasi PIN tidak cocok.']);
    exit();
}

try {
    // Ambil hash PIN saat ini dari database
    $stmt = $pdo->prepare("SELECT pin_hash FROM users WHERE id = ?");
    $stmt->execute([$authenticated_user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Jika user sudah punya PIN, verifikasi PIN lama
    if ($user['pin_hash'] !== null) {
        if (empty($old_pin)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'PIN lama wajib diisi untuk mengubah PIN.']);
            exit();
        }
        if (!password_verify($old_pin, $user['pin_hash'])) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'PIN lama Anda salah.']);
            exit();
        }
    }

    // Hash PIN baru dan simpan ke database
    $new_pin_hash = password_hash($new_pin, PASSWORD_DEFAULT);
    $stmt_update = $pdo->prepare("UPDATE users SET pin_hash = ? WHERE id = ?");
    $stmt_update->execute([$new_pin_hash, $authenticated_user_id]);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'PIN transaksi berhasil diperbarui.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui PIN: ' . $e->getMessage()]);
}
?>

