<?php
// File: app/admin_faq_add.php
// Penjelasan: Admin menambahkan entri FAQ baru.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: CS, Kepala Unit ke atas
$allowed_roles = [1, 2, 3, 6];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$required = ['question', 'answer', 'category'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}

try {
    $stmt = $pdo->prepare("INSERT INTO faqs (question, answer, category) VALUES (?, ?, ?)");
    $stmt->execute([$input['question'], $input['answer'], $input['category']]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'FAQ berhasil ditambahkan.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan FAQ.']);
}
?>