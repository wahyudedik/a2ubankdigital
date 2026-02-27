<?php
// File: app/user_create_standing_instruction.php
// Penjelasan: Nasabah mengatur transfer rutin/berulang.

require_once 'auth_middleware.php';



$input = json_decode(file_get_contents('php://input'), true);
// Validasi input (amount, frequency, execution_day, etc.)
// ...

try {
    $stmt_from = $pdo->prepare("SELECT id FROM accounts WHERE user_id = ? AND account_type = 'TABUNGAN' AND status = 'ACTIVE'");
    $stmt_from->execute([$authenticated_user_id]);
    $from_account_id = $stmt_from->fetchColumn();
    if (!$from_account_id) throw new Exception("Rekening sumber tidak ditemukan.");

    $sql = "INSERT INTO standing_instructions (user_id, from_account_id, to_account_number, to_bank_code, amount, description, frequency, execution_day, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $authenticated_user_id, $from_account_id, $input['to_account_number'], $input['to_bank_code'] ?? null, 
        $input['amount'], $input['description'] ?? '', $input['frequency'], $input['execution_day'], $input['start_date'], $input['end_date'] ?? null
    ]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Instruksi transfer rutin berhasil dibuat.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuat instruksi: ' . $e->getMessage()]);
}
?>
