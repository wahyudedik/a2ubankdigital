<?php
// File: app/admin_create_global_announcement.php
// Penjelasan: Admin membuat pengumuman untuk semua nasabah.

require_once 'auth_middleware.php';


$allowed_roles = [1, 2, 3, 4]; // Marketing ke atas
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
// Validasi input
// ...

try {
    $stmt = $pdo->prepare("INSERT INTO announcements (title, content, type, start_date, end_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$input['title'], $input['content'], $input['type'], $input['start_date'], $input['end_date'], $authenticated_user_id]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Pengumuman berhasil dibuat.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuat pengumuman.']);
}
?>
