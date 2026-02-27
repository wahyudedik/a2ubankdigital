<?php
// File: app/admin_get_system_logs.php
// Penjelasan: Admin melihat log sistem (error, warning, info).

require_once 'auth_middleware.php';
// ... (cek role Super Admin)



try {
    $level_filter = $_GET['level'] ?? '';
    $limit = (int)($_GET['limit'] ?? 100);

    $sql = "SELECT level, message, context, created_at FROM system_logs";
    $params = [];
    if (!empty($level_filter)) {
        $sql .= " WHERE level = ?";
        $params[] = $level_filter;
    }
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $logs]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil log.']);
}
?>
