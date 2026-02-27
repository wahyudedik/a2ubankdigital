<?php
// File: app/utility_get_interbank_list.php
// Penjelasan: Mengambil daftar bank untuk transfer antar bank.

require_once 'config.php';


try {
    $stmt = $pdo->query("SELECT bank_name, bank_code FROM external_banks WHERE is_active = 1 ORDER BY bank_name ASC");
    $banks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $banks]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil daftar bank.']);
}
?>
