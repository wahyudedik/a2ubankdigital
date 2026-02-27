<?php
// File: app/user_get_cards.php
// Penjelasan: Mengambil daftar kartu nasabah, sekarang dengan nama lengkap pemilik.

require_once 'auth_middleware.php';

try {
    // PERBAIKAN: Menambahkan JOIN ke tabel users untuk mendapatkan nama lengkap (full_name)
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.card_number_masked,
            c.status,
            c.expiry_date,
            c.daily_limit,
            a.account_number,
            u.full_name 
        FROM cards c
        JOIN users u ON c.user_id = u.id
        JOIN accounts a ON c.account_id = a.id
        WHERE c.user_id = ?
        ORDER BY c.requested_at DESC
    ");
    $stmt->execute([$authenticated_user_id]);
    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $cards]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data kartu.']);
}
?>
