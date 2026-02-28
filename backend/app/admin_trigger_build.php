<?php
// File: backend/app/admin_trigger_build.php
// Endpoint untuk trigger build frontend dari browser

require_once 'config.php';

// SECURITY: Hanya admin yang bisa trigger build
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (empty($authHeader)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized - Token required']);
    exit;
}

// Verify JWT token
try {
    $token = str_replace('Bearer ', '', $authHeader);
    $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($_ENV['JWT_SECRET'], 'HS256'));
    
    // Hanya Admin/Staff (bukan customer role_id = 9) yang bisa trigger build
    if ($decoded->role_id == 9) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden - Admin/Staff only']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid token: ' . $e->getMessage()]);
    exit;
}

// Path ke folder frontend (2 level up dari backend/app/)
$frontendPath = dirname(dirname(__DIR__)) . '/frontend';

// Check if frontend folder exists
if (!is_dir($frontendPath)) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Frontend folder not found',
        'path' => $frontendPath
    ]);
    exit;
}

// Check if we're on Windows (development) or Linux (production)
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

if ($isWindows) {
    // Windows (Development) - Use PowerShell
    $command = "powershell -Command \"cd '" . $frontendPath . "'; npm run build 2>&1\"";
} else {
    // Linux (Production) - Use bash
    $command = "cd " . escapeshellarg($frontendPath) . " && npm run build 2>&1";
}

// Execute build
$output = [];
$returnCode = 0;
exec($command, $output, $returnCode);

// Return result
if ($returnCode === 0) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Build completed successfully',
        'output' => implode("\n", $output),
        'timestamp' => date('Y-m-d H:i:s'),
        'platform' => $isWindows ? 'Windows' : 'Linux'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Build failed',
        'output' => implode("\n", $output),
        'return_code' => $returnCode,
        'command' => $command
    ]);
}
?>
