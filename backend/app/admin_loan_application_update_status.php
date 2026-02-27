<?php
// File: app/admin_loan_application_update_status.php
// Penjelasan: Staf menyetujui/menolak pengajuan pinjaman dan mengirim notifikasi.
// REVISI: Menambahkan validasi data scoping.

require_once 'auth_middleware.php';
require_once __DIR__ . '/helpers/notification_helper.php';
require_once __DIR__ . '/helpers/push_notification_helper.php';

// Hanya Kepala Unit ke atas yang bisa mengubah status
if ($authenticated_user_role_id > 3) { 
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Peran Anda tidak diizinkan.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$loan_id = $input['loan_id'] ?? 0;
$new_status = $input['status'] ?? '';
$valid_statuses = ['APPROVED', 'REJECTED'];

if ($loan_id <= 0 || !in_array($new_status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Input tidak valid.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Dapatkan data pinjaman untuk validasi & notifikasi
    $stmt_loan_info = $pdo->prepare("
        SELECT l.user_id, u.full_name, lp.product_name, cp.unit_id
        FROM loans l 
        JOIN users u ON l.user_id = u.id
        JOIN customer_profiles cp ON u.id = cp.user_id
        JOIN loan_products lp ON l.loan_product_id = lp.id
        WHERE l.id = ? AND l.status = 'SUBMITTED' FOR UPDATE
    ");
    $stmt_loan_info->execute([$loan_id]);
    $loan_info = $stmt_loan_info->fetch(PDO::FETCH_ASSOC);

    if (!$loan_info) {
        throw new Exception("Data pinjaman tidak ditemukan atau sudah diproses.");
    }
    
    // --- PENAMBAHAN: VALIDASI DATA SCOPING ---
    if ($authenticated_user_role_id !== 1 && !in_array($loan_info['unit_id'], $accessible_unit_ids)) {
        http_response_code(403);
        throw new Exception("Akses ditolak: Anda tidak berwenang memproses pinjaman untuk nasabah di unit ini.");
    }
    // --- AKHIR PENAMBAHAN ---

    // 2. Update status pinjaman di database
    $sql = "UPDATE loans SET status = ?, approved_by = ?, approval_date = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$new_status, $authenticated_user_id, $loan_id]);

    if ($stmt->rowCount() === 0) {
        // Kondisi ini seharusnya tidak terjadi karena sudah dikunci dengan FOR UPDATE
        throw new Exception("Gagal memperbarui status. Pinjaman mungkin sudah diproses oleh admin lain.");
    }

    // --- PENAMBAHAN LOG AUDIT ---
    $action_log = ($new_status === 'APPROVED') ? 'APPROVE_LOAN' : 'REJECT_LOAN';
    $audit_details = json_encode(['loan_id' => $loan_id]);
    $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt_audit->execute([$authenticated_user_id, $action_log, $audit_details, $_SERVER['REMOTE_ADDR']]);
    // --- AKHIR PENAMBAHAN ---

    $pdo->commit();

    // --- Kirim Notifikasi setelah proses berhasil ---
    try {
        $customer_user_id = $loan_info['user_id'];
        $customer_name = $loan_info['full_name'];
        $product_name = $loan_info['product_name'];
        
        // a. Notifikasi untuk Nasabah
        if ($new_status === 'APPROVED') {
            $title = "Pengajuan Pinjaman Disetujui";
            $message = "Selamat! Pengajuan pinjaman Anda untuk produk '$product_name' telah disetujui dan siap untuk dicairkan.";
            
            // Kirim notifikasi ke manajer juga
            $staff_title = "Persetujuan Pinjaman";
            $staff_message = "Pengajuan pinjaman untuk nasabah $customer_name ($product_name) telah disetujui dan menunggu pencairan.";
            notify_staff_by_role($pdo, [1, 2, 3], $staff_title, $staff_message); // KaUnit, KaCab, Super Admin

        } else { // REJECTED
            $title = "Pengajuan Pinjaman Ditolak";
            $message = "Mohon maaf, pengajuan pinjaman Anda untuk produk '$product_name' belum dapat kami setujui saat ini.";
        }

        // Simpan notifikasi ke database (untuk ikon lonceng)
        $stmt_notify_customer = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $stmt_notify_customer->execute([$customer_user_id, $title, $message]);

        // Kirim notifikasi push ke perangkat nasabah
        sendPushNotification($pdo, $customer_user_id, $title, $message);

    } catch (Exception $e) {
        error_log("Gagal mengirim notifikasi persetujuan pinjaman: " . $e->getMessage());
    }
    // --------------------------------------------------

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Status pengajuan pinjaman berhasil diperbarui.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // Mengirimkan response code yang sesuai jika ada error otorisasi
    $code = http_response_code() >= 400 ? http_response_code() : 500;
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui status: ' . $e->getMessage()]);
}

