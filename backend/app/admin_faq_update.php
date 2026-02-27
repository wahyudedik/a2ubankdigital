<?php
// File: app/admin_faq_update.php
// Penjelasan: Admin memperbarui entri FAQ yang ada.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: CS, Kepala Unit ke atas
$allowed_roles = [1, 2, 3, 6];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$faq_id = $input['id'] ?? null;
if (!$faq_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID FAQ wajib diisi.']);
    exit();
}

try {
    $update_fields = [];
    $params = [];
    $allowed_fields = ['question', 'answer', 'category', 'is_active'];

    foreach ($allowed_fields as $field) {
        if (isset($input[$field])) {
            $update_fields[] = "$field = ?";
            $params[] = $input[$field];
        }
    }
    if (empty($update_fields)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada data untuk diperbarui.']);
        exit();
    }
    
    $params[] = $faq_id;
    $sql = "UPDATE faqs SET " . implode(', ', $update_fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'FAQ berhasil diperbarui.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui FAQ.']);
}
?>
