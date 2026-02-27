<?php
// File: app/admin_process_credit_limit_request.php
// Penjelasan: Staf memproses pengajuan kenaikan limit.

require_once 'auth_middleware.php';
// ... (cek role bagian kredit/analis)

$input = json_decode(file_get_contents('php://input'), true);
$request_id = $input['request_id'] ?? null;
$new_status = $input['status'] ?? '';

// Validasi input
// ...

try {
    $pdo->beginTransaction();

    // 1. Update status pengajuan
    $stmt_req = $pdo->prepare("UPDATE limit_increase_requests SET status = ? WHERE id = ? AND status = 'PENDING'");
    $stmt_req->execute([$new_status, $request_id]);
    if ($stmt_req->rowCount() == 0) throw new Exception("Pengajuan tidak ditemukan atau sudah diproses.");
    
    // 2. Jika disetujui, update limit di rekening
    if ($new_status === 'APPROVED') {
        $stmt_get = $pdo->prepare("SELECT account_id, requested_limit FROM limit_increase_requests WHERE id = ?");
        $stmt_get->execute([$request_id]);
        $req_data = $stmt_get->fetch(PDO::FETCH_ASSOC);

        // Di sistem nyata, kolom `credit_limit` akan ada di tabel `accounts`
        // $stmt_update_limit = $pdo->prepare("UPDATE accounts SET credit_limit = ? WHERE id = ?");
        // $stmt_update_limit->execute([$req_data['requested_limit'], $req_data['account_id']]);
    }

    $pdo->commit();
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Pengajuan berhasil diproses.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memproses pengajuan: ' . $e->getMessage()]);
}
?>
