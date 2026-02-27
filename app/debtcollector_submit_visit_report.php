<?php
// File: app/debtcollector_submit_visit_report.php
// Penjelasan: Debt Collector membuat laporan kunjungan.

require_once 'auth_middleware.php';
// ... (cek role Debt Collector)


$input = json_decode(file_get_contents('php://input'), true);
// Validasi input
// ...

try {
    $sql = "INSERT INTO collection_visit_reports (assignment_id, collector_id, visit_date, outcome, notes, next_action_date) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $input['assignment_id'], $authenticated_user_id, $input['visit_date'], 
        $input['outcome'], $input['notes'] ?? '', $input['next_action_date'] ?? null
    ]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Laporan kunjungan berhasil disimpan.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan laporan.']);
}
?>
