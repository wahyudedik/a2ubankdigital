<?php
// File: app/utility_upload_file.php
// Penjelasan: Nasabah mengunggah file (misal: KTP, bukti bayar).

require_once 'auth_middleware.php';



$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if (isset($_FILES['document'])) {
    $file = $_FILES['document'];
    $purpose = $_POST['purpose'] ?? 'general';
    
    // Validasi file (ukuran, tipe, dll.)
    // ...

    $file_name = time() . '_' . basename($file['name']);
    $target_path = $upload_dir . $file_name;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO uploaded_documents (user_id, file_name, file_path, file_type, file_size, purpose) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$authenticated_user_id, $file_name, $target_path, $file['type'], $file['size'], $purpose]);

            http_response_code(201);
            echo json_encode(['status' => 'success', 'message' => 'File berhasil diunggah.', 'file_path' => '/uploads/' . $file_name]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan info file.']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal memindahkan file.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tidak ada file yang diunggah.']);
}
?>
