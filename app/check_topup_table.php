<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5174');
header('Access-Control-Allow-Credentials: true');

require_once 'config.php';

try {
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'topup_requests'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Table topup_requests does not exist!',
            'solution' => 'Need to create table'
        ]);
        exit;
    }
    
    // Get table structure
    $stmt = $pdo->query("DESCRIBE topup_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get sample data
    $stmt = $pdo->query("SELECT * FROM topup_requests ORDER BY id DESC LIMIT 5");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'table_exists' => true,
        'columns' => $columns,
        'sample_data' => $data,
        'total_records' => $pdo->query("SELECT COUNT(*) FROM topup_requests")->fetchColumn()
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
