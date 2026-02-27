<?php
// File: app/admin_marketing_get_customer_segments.php
// Penjelasan: Marketing mendapatkan daftar nasabah berdasarkan kriteria.

require_once 'auth_middleware.php';

// Role yang bisa mengakses: Marketing, Kepala Unit ke atas
$allowed_roles = [1, 2, 3, 4];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$segment_type = $_GET['segment'] ?? ''; // Contoh: 'high_balance', 'frequent_transactors'
$limit = $_GET['limit'] ?? 100;

try {
    $sql = "";
    $params = [];

    switch ($segment_type) {
        case 'high_balance':
            $min_balance = $_GET['min_balance'] ?? 50000000;
            $sql = "SELECT u.id, u.full_name, u.email, u.phone, a.balance
                    FROM users u
                    JOIN accounts a ON u.id = a.user_id
                    WHERE u.role_id = 9 AND a.account_type = 'TABUNGAN' AND a.balance >= ?
                    ORDER BY a.balance DESC LIMIT ?";
            $params = [$min_balance, (int)$limit];
            break;
        
        case 'frequent_transactors':
            $min_trx_count = $_GET['min_trx'] ?? 20;
            $days = $_GET['days'] ?? 30;
            $sql = "SELECT u.id, u.full_name, u.email, COUNT(t.id) as trx_count
                    FROM users u
                    JOIN accounts a ON u.id = a.user_id
                    JOIN transactions t ON a.id = t.from_account_id
                    WHERE u.role_id = 9 AND t.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY u.id, u.full_name, u.email
                    HAVING trx_count >= ?
                    ORDER BY trx_count DESC LIMIT ?";
            $params = [(int)$days, $min_trx_count, (int)$limit];
            break;

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Tipe segmen tidak valid.']);
            exit();
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $customers]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil segmen nasabah: ' . $e->getMessage()]);
}
?>
