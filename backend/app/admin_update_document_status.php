<?php
// File: app/admin_update_document_status.php
// Penjelasan: Staf menyetujui atau menolak dokumen yang diunggah.

require_once 'auth_middleware.php';

/*
    CATATAN DATABASE: Menambahkan kolom status di `uploaded_documents`.
    ALTER TABLE `uploaded_documents` 
    ADD `status` ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING' AFTER `purpose`,
    ADD `review_notes` TEXT NULL DEFAULT NULL AFTER `status`,
    ADD `reviewed_by` INT(10) UNSIGNED NULL DEFAULT NULL AFTER `review_notes`;
*/

$allowed_roles = [1, 2, 3, 4, 5, 6]; 
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$document_id = $input['document_id'] ?? null;
$new_status = $input['status'] ?? '';
$review_notes = $input['notes'] ?? '';

if (!$document_id || !in_array($new_status, ['APPROVED', 'REJECTED'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Input tidak valid.']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE uploaded_documents SET status = ?, review_notes = ?, reviewed_by = ? WHERE id = ?");
    $stmt->execute([$new_status, $review_notes, $authenticated_user_id, $document_id]);

    if ($stmt->rowCount() > 0) {
        // Kirim notifikasi ke nasabah
        // ...
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Status dokumen berhasil diperbarui.']);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Dokumen tidak ditemukan.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui status dokumen.']);
}
?>