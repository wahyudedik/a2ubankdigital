<?php
// File: app/admin_review_uploaded_document.php
// Penjelasan: Staf meninjau dokumen yang diunggah nasabah.

require_once 'auth_middleware.php';

$allowed_roles = [1, 2, 3, 4, 5, 6]; // Semua staf kecuali Debt Collector
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$user_id_filter = $_GET['user_id'] ?? null;

try {
    $sql = "SELECT d.id, d.user_id, u.full_name, d.file_name, d.file_path, d.purpose, d.uploaded_at FROM uploaded_documents d JOIN users u ON d.user_id = u.id";
    $params = [];

    if ($user_id_filter) {
        $sql .= " WHERE d.user_id = ?";
        $params[] = $user_id_filter;
    }

    $sql .= " ORDER BY d.uploaded_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $documents]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data dokumen.']);
}
?>
