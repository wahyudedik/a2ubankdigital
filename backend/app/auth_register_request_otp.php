<?php
// File: app/auth_register_request_otp.php
// Penjelasan: Menangani pendaftaran lengkap dengan unggahan file KTP dan selfie.

// Konfigurasi dan helper yang diperlukan
require_once 'config.php';
require_once __DIR__ . '/helpers/email_helper.php';

// --- FUNGSI HELPER UNTUK UPLOAD FILE ---
function handle_file_upload($file_key, $user_nik) {
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File untuk '{$file_key}' wajib diunggah dan tidak boleh ada error.");
    }
    
    $file = $_FILES[$file_key];
    $upload_dir = BASE_PATH . '/uploads/documents/';

    // Buat direktori jika belum ada
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0775, true)) {
            throw new Exception("Gagal membuat direktori unggahan.");
        }
    }

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png'];
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception("Hanya file JPG, JPEG, dan PNG yang diizinkan.");
    }

    if ($file['size'] > 2097152) { // 2MB
        throw new Exception("Ukuran file maksimal adalah 2MB.");
    }

    $file_name = $user_nik . '_' . $file_key . '_' . time() . '.' . $file_extension;
    $target_path = $upload_dir . $file_name;
    $web_path = '/uploads/documents/' . $file_name;

    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        throw new Exception("Gagal memindahkan file yang diunggah.");
    }

    return $web_path;
}
// --- AKHIR FUNGSI HELPER ---

// Karena menggunakan multipart/form-data, kita ambil data dari $_POST
$input = $_POST;

// Validasi input teks
$required = ['full_name', 'email', 'password', 'nik', 'mother_maiden_name', 'pob', 'dob', 'gender', 'address_ktp', 'phone_number', 'unit_id'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit();
    }
}

$full_name = trim($input['full_name']);
$email = trim($input['email']);
$password = $input['password'];
$unit_id = (int)$input['unit_id'];
$role_id_nasabah = 9;
$nik_for_filename = preg_replace("/[^a-zA-Z0-9]/", "", $input['nik']); // Sanitasi NIK untuk nama file

$ktp_path = null;
$selfie_path = null;

try {
    // Cek duplikasi email atau NIK
    $stmt_check = $pdo->prepare("
        SELECT u.id 
        FROM users u 
        LEFT JOIN customer_profiles cp ON u.id = cp.user_id 
        WHERE u.email = ? OR cp.nik = ?
    ");
    $stmt_check->execute([$email, $input['nik']]);
    if ($stmt_check->fetch()) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Email atau NIK sudah terdaftar.']);
        exit();
    }

    // Proses unggahan file sebelum transaksi database
    $ktp_path = handle_file_upload('ktp_image', $nik_for_filename);
    $selfie_path = handle_file_upload('selfie_image', $nik_for_filename);
    
    // Hapus user/OTP lama yang mungkin tertunda untuk email yang sama
    $pdo->prepare("DELETE FROM users WHERE email = ? AND status = 'PENDING_VERIFICATION'")->execute([$email]);

    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $pdo->beginTransaction();
    
    // Buat user baru dengan status PENDING_VERIFICATION
    $stmt_user = $pdo->prepare(
        "INSERT INTO users (role_id, full_name, email, password_hash, phone_number, status, unit_id) 
         VALUES (?, ?, ?, ?, ?, 'PENDING_VERIFICATION', ?)"
    );
    $stmt_user->execute([$role_id_nasabah, $full_name, $email, $password_hash, $input['phone_number'], $unit_id]);
    $user_id = $pdo->lastInsertId();
    
    // Simpan data profil lengkap ke customer_profiles, termasuk path gambar
    $stmt_profile = $pdo->prepare(
        "INSERT INTO customer_profiles (user_id, unit_id, nik, mother_maiden_name, pob, dob, gender, address_ktp, ktp_image_path, selfie_image_path, registration_method, kyc_status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ONLINE', 'APPROVED')"
    );
    $stmt_profile->execute([
        $user_id, 
        $unit_id,
        $input['nik'], 
        $input['mother_maiden_name'], 
        $input['pob'], 
        $input['dob'], 
        $input['gender'], 
        $input['address_ktp'],
        $ktp_path,
        $selfie_path
    ]);

    // Buat dan simpan OTP
    $otp_code = rand(100000, 999999);
    $expires_at = date('Y-m-d H:i:s', time() + 600); // 10 menit
    $stmt_otp = $pdo->prepare("INSERT INTO user_otps (user_id, otp_code, expires_at) VALUES (?, ?, ?)");
    $stmt_otp->execute([$user_id, $otp_code, $expires_at]);

    // Kirim email OTP
    $template_data = [
        'preheader' => 'Kode verifikasi pendaftaran Anda.',
        'full_name' => $full_name,
        'otp_code' => $otp_code
    ];
    $email_sent = send_email($email, $full_name, 'Kode Verifikasi Pendaftaran', 'otp_template', $template_data);

    if ($email_sent) {
        $pdo->commit();
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'OTP telah dikirim ke email Anda.']);
    } else {
        throw new Exception("Gagal mengirim email verifikasi2.");
    }

} catch (Exception $e) {
    // Jika terjadi error setelah file diunggah tapi sebelum commit, hapus file tersebut
    if ($ktp_path && file_exists(BASE_PATH . $ktp_path)) {
        unlink(BASE_PATH . $ktp_path);
    }
    if ($selfie_path && file_exists(BASE_PATH . $selfie_path)) {
        unlink(BASE_PATH . $selfie_path);
    }

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
