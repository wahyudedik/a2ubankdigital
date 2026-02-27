<?php
// File: app/admin_get_daily_reconciliation_report.php
// Penjelasan: Laporan untuk rekonsiliasi transaksi harian.

require_once 'auth_middleware.php';

$allowed_roles = [1, 2, 3]; // Kepala Unit ke atas
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$report_date = $_GET['date'] ?? date('Y-m-d');

try {
    $sql = "
        SELECT 
            SUM(CASE WHEN transaction_type IN ('SETOR_TUNAI', 'TRANSFER_INTERNAL', 'BUNGA_TABUNGAN', 'PENCAIRAN_DEPOSITO') THEN amount ELSE 0 END) as total_credit,
            SUM(CASE WHEN transaction_type IN ('TARIK_TUNAI', 'PEMBELIAN_PRODUK', 'PEMBAYARAN_TAGIHAN', 'BIAYA_ADMIN', 'PEMBAYARAN_PINJAMAN') THEN amount ELSE 0 END) as total_debit
        FROM transactions
        WHERE DATE(created_at) = ? AND status = 'SUCCESS'
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$report_date]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    $report['total_credit'] = (float)($report['total_credit'] ?? 0);
    $report['total_debit'] = (float)($report['total_debit'] ?? 0);
    // Note: This is a simplified reconciliation. A real one would be more complex.

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $report]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil laporan rekonsiliasi: ' . $e->getMessage()]);
}
?>
